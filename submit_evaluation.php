<?php
include_once 'session_config.php';
session_start();
header("Content-Type: application/json");
include "connect.php";
date_default_timezone_set("Asia/Manila");

$conn->query("
    CREATE TABLE IF NOT EXISTS evaluation_periods (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        ay VARCHAR(20) NOT NULL,
        semester VARCHAR(20) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$conn->query("
    CREATE TABLE IF NOT EXISTS evaluation_settings (
        id INT(11) NOT NULL PRIMARY KEY,
        evaluation_open TINYINT(1) NOT NULL DEFAULT 0,
        active_period_id INT(11) NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_active_period (active_period_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
$conn->query("INSERT IGNORE INTO evaluation_settings (id, evaluation_open, active_period_id) VALUES (1, 0, NULL)");
$today = date("Y-m-d");
$expiredStmt = $conn->prepare("
    SELECT es.active_period_id
    FROM evaluation_settings es
    JOIN evaluation_periods ep ON ep.id = es.active_period_id
    WHERE es.id = 1
      AND es.active_period_id IS NOT NULL
      AND ep.end_date < ?
    LIMIT 1
");
$expiredStmt->bind_param("s", $today);
$expiredStmt->execute();
$expired = $expiredStmt->get_result()->fetch_assoc();
$expiredStmt->close();
if ($expired) {
    $conn->query("UPDATE evaluation_periods SET is_active = 0");
    $conn->query("UPDATE evaluation_settings SET evaluation_open = 0, active_period_id = NULL WHERE id = 1");
}

$settingsRes = $conn->query("SELECT evaluation_open, active_period_id FROM evaluation_settings WHERE id = 1 LIMIT 1");
$settings = $settingsRes ? $settingsRes->fetch_assoc() : null;
$evaluationOpen = $settings ? intval($settings["evaluation_open"]) === 1 : false;
$activePeriodId = $settings ? intval($settings["active_period_id"] ?? 0) : 0;

if ($evaluationOpen && $activePeriodId > 0) {
    $periodStmt = $conn->prepare("SELECT start_date, end_date FROM evaluation_periods WHERE id = ? LIMIT 1");
    $periodStmt->bind_param("i", $activePeriodId);
    $periodStmt->execute();
    $period = $periodStmt->get_result()->fetch_assoc();
    $periodStmt->close();
    if (!$period || $period["start_date"] > $today || $period["end_date"] < $today) {
        $evaluationOpen = false;
    }
}

if (!$evaluationOpen || $activePeriodId <= 0) {
    echo json_encode(["success" => false, "message" => "Evaluation is closed"]);
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$payload = json_decode(file_get_contents("php://input"), true);
$student_id = intval($_SESSION['id']);

$studentStatusStmt = $conn->prepare("SELECT status FROM add_students WHERE id = ? LIMIT 1");
$studentStatusStmt->bind_param("i", $student_id);
$studentStatusStmt->execute();
$studentStatusRow = $studentStatusStmt->get_result()->fetch_assoc();
$studentStatusStmt->close();

if (strtolower((string)($studentStatusRow['status'] ?? 'active')) !== 'active') {
    echo json_encode([
        "success" => false,
        "message" => "Your account has been set to inactive by an admin. You cannot evaluate until your account is active again."
    ]);
    exit;
}

$faculty_id = intval($payload['faculty_id'] ?? 0);
$answers = $payload['answers'] ?? [];
$feedback = trim($payload['feedback'] ?? '');

if ($faculty_id <= 0 || !is_array($answers) || empty($answers)) {
    echo json_encode(["success" => false, "message" => "Invalid evaluation payload"]);
    exit;
}

// Block feedback that contains bad words (server-side enforcement).
if ($feedback !== '') {
    $badWords = [
        "putangina",
        "puta",
        "tangina",
        "tang ina",
        "gago",
        "tanga",
        "bobo",
        "ulol",
        "tarantado",
        "inutil",
        "leche",
        "bwiset",
        "bwisit",
        "punyeta",
        "fuck you",
        "fuck",
        "shit",
        "bitch",
        "asshole",
        "dick",
        "cunt",
        "faggot",
        "nigger"
    ];
    $normalized = ' ' . preg_replace('/\s+/', ' ', trim(preg_replace('/[^a-z0-9]+/i', ' ', strtolower($feedback)))) . ' ';
    foreach ($badWords as $w) {
        if (strpos($normalized, ' ' . $w . ' ') !== false) {
            echo json_encode(["success" => false, "message" => "Bad words is not allowed"]);
            exit;
        }
    }
}

// Ensure evaluation tables exist.
$conn->query("
    CREATE TABLE IF NOT EXISTS evaluations (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        student_id INT(11) NOT NULL,
        faculty_id INT(11) NOT NULL,
        overall_rating DECIMAL(4,2) NOT NULL DEFAULT 0.00,
        feedback TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_student_faculty (student_id, faculty_id),
        KEY idx_eval_faculty (faculty_id),
        KEY idx_eval_student (student_id)
    )
");

$conn->query("
    CREATE TABLE IF NOT EXISTS evaluation_answers (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        evaluation_id INT(11) NOT NULL,
        question_id INT(11) NOT NULL,
        rating TINYINT(1) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_eval_question (evaluation_id, question_id),
        KEY idx_answer_eval (evaluation_id)
    )
");

// Validate and compute overall rating.
$sum = 0;
$count = 0;
$normalizedAnswers = [];
foreach ($answers as $key => $value) {
    if (!preg_match('/^q_(\d+)$/', (string)$key, $m)) {
        continue;
    }
    $question_id = intval($m[1]);
    $rating = intval($value);
    if ($question_id <= 0 || $rating < 1 || $rating > 5) {
        continue;
    }
    $normalizedAnswers[] = ["question_id" => $question_id, "rating" => $rating];
    $sum += $rating;
    $count++;
}

if ($count === 0) {
    echo json_encode(["success" => false, "message" => "No valid answers found"]);
    exit;
}

// Use weighted category scoring instead of a flat mean.
require_once 'weighted_score_helper.php';
$overall = calcWeightedScoreFromAnswers($conn, $normalizedAnswers);
// Fallback to flat mean if weighted calculation returns 0 unexpectedly
if ($overall <= 0 && $count > 0) {
    $overall = round($sum / $count, 2);
}

$conn->begin_transaction();
try {
    // Upsert one response per student/faculty.
    $upsert = $conn->prepare("
        INSERT INTO evaluations (student_id, faculty_id, overall_rating, feedback)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE overall_rating = VALUES(overall_rating), feedback = VALUES(feedback)
    ");
    $upsert->bind_param("iids", $student_id, $faculty_id, $overall, $feedback);
    $upsert->execute();
    $upsert->close();

    $evalStmt = $conn->prepare("SELECT id FROM evaluations WHERE student_id = ? AND faculty_id = ? LIMIT 1");
    $evalStmt->bind_param("ii", $student_id, $faculty_id);
    $evalStmt->execute();
    $evalRes = $evalStmt->get_result();
    $evalRow = $evalRes->fetch_assoc();
    $evalStmt->close();

    if (!$evalRow) {
        throw new Exception("Failed to resolve evaluation ID");
    }
    $evaluation_id = intval($evalRow['id']);

    $del = $conn->prepare("DELETE FROM evaluation_answers WHERE evaluation_id = ?");
    $del->bind_param("i", $evaluation_id);
    $del->execute();
    $del->close();

    $ins = $conn->prepare("INSERT INTO evaluation_answers (evaluation_id, question_id, rating) VALUES (?, ?, ?)");
    foreach ($normalizedAnswers as $row) {
        $qid = $row['question_id'];
        $rt = $row['rating'];
        $ins->bind_param("iii", $evaluation_id, $qid, $rt);
        $ins->execute();
    }
    $ins->close();

    $conn->commit();
    echo json_encode([
        "success" => true,
        "message" => "Evaluation submitted successfully",
        "overall_rating" => $overall
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "success" => false,
        "message" => "Submission failed: " . $e->getMessage()
    ]);
}

$conn->close();
?>

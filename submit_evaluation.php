<?php
ob_start();
ini_set('display_errors', '0');
require_once 'security.php';
requireRole('student');
requireMethod('POST');
include_once 'session_config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header("Content-Type: application/json");
include "connect.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once 'evaluation_schema.php';
require_once 'evaluation_period_helper.php';
date_default_timezone_set("Asia/Manila");

function submitEvaluationResponse(array $payload, int $status = 200): never
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($status);
    header("Content-Type: application/json");
    echo json_encode($payload);
    exit;
}

set_exception_handler(static function (Throwable $e): void {
    error_log("submit_evaluation.php error: " . $e->getMessage());
    submitEvaluationResponse([
        "success" => false,
        "message" => "Submission failed. Please try again."
    ], 500);
});

ensureEvaluationPeriodTables($conn);
closeExpiredEvaluationPeriod($conn);
$settingsRes = $conn->query("SELECT evaluation_open, active_period_id FROM evaluation_settings WHERE id = 1 LIMIT 1");
$settings = $settingsRes ? $settingsRes->fetch_assoc() : null;
$evaluationOpen = $settings ? intval($settings["evaluation_open"]) === 1 : false;
$activePeriodId = $settings ? intval($settings["active_period_id"] ?? 0) : 0;
$today = date("Y-m-d");

if ($evaluationOpen && $activePeriodId > 0) {
    $periodStmt = $conn->prepare("SELECT start_date, end_date FROM evaluation_periods WHERE id = ? LIMIT 1");
    $periodStmt->bind_param("i", $activePeriodId);
    $periodStmt->execute();
    $period = $periodStmt->get_result()->fetch_assoc();
    $periodStmt->close();
    if (!$period
        || empty($period["start_date"])
        || empty($period["end_date"])
        || $period["start_date"] > $today
        || $period["end_date"] < $today) {
        $evaluationOpen = false;
    }
}

if (!$evaluationOpen || $activePeriodId <= 0) {
    submitEvaluationResponse(["success" => false, "message" => "Evaluation is closed"], 409);
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['id'])) {
    submitEvaluationResponse(["success" => false, "message" => "Unauthorized"], 403);
}

$payload = json_decode(file_get_contents("php://input"), true);
$student_id = intval($_SESSION['id']);

$studentStatusStmt = $conn->prepare("SELECT status FROM add_students WHERE id = ? LIMIT 1");
$studentStatusStmt->bind_param("i", $student_id);
$studentStatusStmt->execute();
$studentStatusRow = $studentStatusStmt->get_result()->fetch_assoc();
$studentStatusStmt->close();

if (strtolower((string)($studentStatusRow['status'] ?? 'active')) !== 'active') {
    submitEvaluationResponse([
        "success" => false,
        "message" => "Your account has been set to inactive by an admin. You cannot evaluate until your account is active again."
    ], 403);
}

$faculty_id = intval($payload['faculty_id'] ?? 0);
$subject_id = intval($payload['subject_id'] ?? 0);
$class_id = intval($payload['class_id'] ?? 0);
$answers = $payload['answers'] ?? [];
$feedback = trim($payload['feedback'] ?? '');

if ($faculty_id <= 0 || $subject_id <= 0 || !is_array($answers) || empty($answers)) {
    submitEvaluationResponse(["success" => false, "message" => "Invalid evaluation payload"], 422);
}

$submittedQuestionIds = [];
foreach (array_keys($answers) as $answerKey) {
    if (preg_match('/^q_(\d+)$/', (string)$answerKey, $match)) {
        $submittedQuestionIds[] = intval($match[1]);
    }
}
$submittedQuestionIds = array_values(array_unique(array_filter($submittedQuestionIds, static fn($id) => $id > 0)));
if (empty($submittedQuestionIds)) {
    submitEvaluationResponse(["success" => false, "message" => "No valid questions submitted"], 422);
}

$questionCheck = $conn->query("SELECT id FROM add_questions");
$knownQuestionIds = [];
if ($questionCheck) {
    while ($questionRow = $questionCheck->fetch_assoc()) {
        $knownQuestionIds[] = intval($questionRow['id']);
    }
}
if (count(array_diff($submittedQuestionIds, $knownQuestionIds)) > 0) {
    submitEvaluationResponse(["success" => false, "message" => "Invalid evaluation question submitted"], 422);
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
            submitEvaluationResponse(["success" => false, "message" => "Bad words is not allowed"], 422);
        }
    }
}

// Ensure evaluation tables exist.
ensureEvaluationsSchema($conn);

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
    submitEvaluationResponse(["success" => false, "message" => "No valid answers found"], 422);
}

$assignmentValid = false;
if ($class_id > 0) {
    $assignmentStmt = $conn->prepare("
        SELECT cs.class_id
        FROM class_subjects cs
        LEFT JOIN student_subject_classes ssc
            ON ssc.student_id = ?
           AND ssc.subject_id = cs.subject_id
           AND ssc.class_id = cs.class_id
        LEFT JOIN add_students st ON st.id = ?
        LEFT JOIN add_classes ac
            ON ac.id = cs.class_id
           AND (ac.year_level = st.yearlevel OR ac.year_level = CONCAT(st.yearlevel, CASE st.yearlevel WHEN '1' THEN 'st Year' WHEN '2' THEN 'nd Year' WHEN '3' THEN 'rd Year' ELSE 'th Year' END))
           AND TRIM(UPPER(ac.block)) = TRIM(UPPER(st.section))
        LEFT JOIN add_programs p ON p.id = ac.program_id
        WHERE cs.faculty_id = ?
          AND cs.subject_id = ?
          AND cs.class_id = ?
          AND (
              ssc.id IS NOT NULL
              OR (
                  ac.id IS NOT NULL
                  AND (
                      CAST(ac.program_id AS CHAR) = TRIM(st.program)
                      OR TRIM(p.program_code) = TRIM(st.program)
                      OR TRIM(p.program_name) = TRIM(st.program)
                  )
              )
          )
        LIMIT 1
    ");
    if ($assignmentStmt) {
        $assignmentStmt->bind_param("iiiii", $student_id, $student_id, $faculty_id, $subject_id, $class_id);
        $assignmentStmt->execute();
        $assignmentValid = (bool)$assignmentStmt->get_result()->fetch_assoc();
        $assignmentStmt->close();
    }
} else {
    $assignmentStmt = $conn->prepare("
        SELECT fs.faculty_id
        FROM faculty_subjects fs
        INNER JOIN student_subjects ss
            ON ss.subject_id = fs.subject_id
           AND ss.student_id = ?
        WHERE fs.faculty_id = ?
          AND fs.subject_id = ?
        LIMIT 1
    ");
    if ($assignmentStmt) {
        $assignmentStmt->bind_param("iii", $student_id, $faculty_id, $subject_id);
        $assignmentStmt->execute();
        $assignmentValid = (bool)$assignmentStmt->get_result()->fetch_assoc();
        $assignmentStmt->close();
    }
}

if (!$assignmentValid) {
    submitEvaluationResponse(["success" => false, "message" => "This faculty-subject assignment is not available for your account"], 403);
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
    // Upsert one response per student/faculty/subject/class.
    $upsert = $conn->prepare("
        INSERT INTO evaluations (student_id, faculty_id, subject_id, class_id, overall_rating, feedback, period_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE overall_rating = VALUES(overall_rating), feedback = VALUES(feedback)
    ");
    if (!$upsert) {
        throw new Exception("Unable to prepare evaluation");
    }
    $upsert->bind_param("iiiidsi", $student_id, $faculty_id, $subject_id, $class_id, $overall, $feedback, $activePeriodId);
    if (!$upsert->execute()) {
        throw new Exception("Unable to save evaluation");
    }
    $upsert->close();

    $evalStmt = $conn->prepare("SELECT id FROM evaluations WHERE student_id = ? AND faculty_id = ? AND subject_id = ? AND class_id = ? AND period_id = ? LIMIT 1");
    $evalStmt->bind_param("iiiii", $student_id, $faculty_id, $subject_id, $class_id, $activePeriodId);
    $evalStmt->execute();
    $evalRes = $evalStmt->get_result();
    $evalRow = $evalRes->fetch_assoc();
    $evalStmt->close();

    if (!$evalRow) {
        throw new Exception("Failed to resolve evaluation ID");
    }
    $evaluation_id = intval($evalRow['id']);

    $del = $conn->prepare("DELETE FROM evaluation_answers WHERE evaluation_id = ?");
    if (!$del) {
        throw new Exception("Unable to prepare evaluation answers");
    }
    $del->bind_param("i", $evaluation_id);
    if (!$del->execute()) {
        throw new Exception("Unable to replace evaluation answers");
    }
    $del->close();

    $ins = $conn->prepare("INSERT INTO evaluation_answers (evaluation_id, question_id, rating) VALUES (?, ?, ?)");
    if (!$ins) {
        throw new Exception("Unable to prepare evaluation answers");
    }
    foreach ($normalizedAnswers as $row) {
        $qid = $row['question_id'];
        $rt = $row['rating'];
        $ins->bind_param("iii", $evaluation_id, $qid, $rt);
        if (!$ins->execute()) {
            throw new Exception("Unable to save evaluation answers");
        }
    }
    $ins->close();

    $conn->commit();
    submitEvaluationResponse([
        "success" => true,
        "message" => "Evaluation submitted successfully",
        "overall_rating" => $overall
    ]);
} catch (Exception $e) {
    $conn->rollback();
    submitEvaluationResponse([
        "success" => false,
        "message" => "Submission failed: " . $e->getMessage()
    ], 500);
}

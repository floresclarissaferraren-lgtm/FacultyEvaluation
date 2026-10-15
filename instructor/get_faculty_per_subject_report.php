<?php
/**
 * get_faculty_per_subject_report.php
 *
 * Returns per-subject evaluation data for the logged-in faculty member
 * within a selected closed evaluation period, grouped by program/department.
 *
 * Method : POST
 * Body   : { "faculty_id": "FC-XXXX" | numeric, "period_id": number }
 * Returns: JSON
 */
require_once __DIR__ . "/../shared/security.php";
header("Content-Type: application/json");

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "faculty") {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit();
}

include __DIR__ . "/../shared/connect.php";
require_once __DIR__ . "/../shared/weighted_score_helper.php";
require_once __DIR__ . "/../shared/evaluation_schema.php";
require_once __DIR__ . "/../shared/evaluation_period_helper.php";

/* ── helpers ───────────────────────────────────────────────────────────── */
function ratingLabel(float $score): string
{
    if ($score <= 0) {
        return "No Responses";
    }
    if ($score >= 4.5) {
        return "Outstanding";
    }
    if ($score >= 3.5) {
        return "Very Good";
    }
    if ($score >= 2.5) {
        return "Good";
    }
    if ($score >= 1.5) {
        return "Fair";
    }
    return "Poor";
}

function ratingClass(float $score): string
{
    if ($score <= 0) {
        return "no-responses";
    }
    if ($score >= 4.5) {
        return "outstanding";
    }
    if ($score >= 3.5) {
        return "very-good";
    }
    if ($score >= 2.5) {
        return "good";
    }
    if ($score >= 1.5) {
        return "fair";
    }
    return "poor";
}

/* ── parse input ───────────────────────────────────────────────────────── */
$data = json_decode(file_get_contents("php://input"), true);
$raw_id = trim($data["faculty_id"] ?? "");
$period_id = intval($data["period_id"] ?? 0);

if ($raw_id === "" || $period_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Faculty ID and evaluation period are required.",
    ]);
    exit();
}

// Resolve string faculty_id (FC-XXXX) → numeric id
if (!is_numeric($raw_id)) {
    $stmt = $conn->prepare("SELECT id FROM add_faculties WHERE faculty_id = ?");
    $stmt->bind_param("s", $raw_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid faculty ID.",
        ]);
        exit();
    }
    $numeric_id = intval($row["id"]);
} else {
    $numeric_id = intval($raw_id);
}

// Security: faculty can only view their own data
if ($numeric_id !== intval($_SESSION["id"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit();
}

// Check faculty active status
$sStmt = $conn->prepare(
    "SELECT status FROM add_faculties WHERE id = ? LIMIT 1",
);
$sStmt->bind_param("i", $numeric_id);
$sStmt->execute();
$sRow = $sStmt->get_result()->fetch_assoc();
$sStmt->close();

if (strtolower((string) ($sRow["status"] ?? "active")) !== "active") {
    echo json_encode([
        "success" => false,
        "inactive" => true,
        "message" => "Your account has been set to inactive by an admin.",
    ]);
    exit();
}

blockFacultyResultsWhileEvaluationOngoing($conn);

ensureEvaluationsSchema($conn);
$periodStmt = $conn->prepare("
    SELECT id, ay, semester, start_date, end_date
    FROM evaluation_periods
    WHERE id = ? AND is_active = 0
      AND EXISTS (
          SELECT 1
          FROM evaluations e
          WHERE e.faculty_id = ?
            AND (
                e.period_id = evaluation_periods.id
                OR (
                    e.period_id IS NULL
                    AND evaluation_periods.start_date IS NOT NULL
                    AND evaluation_periods.end_date IS NOT NULL
                    AND DATE(e.created_at) BETWEEN evaluation_periods.start_date AND evaluation_periods.end_date
                )
            )
      )
    LIMIT 1
");
$periodStmt->bind_param("ii", $period_id, $numeric_id);
$periodStmt->execute();
$evaluationPeriod = $periodStmt->get_result()->fetch_assoc();
$periodStmt->close();
if (!$evaluationPeriod) {
    echo json_encode([
        "success" => false,
        "message" => "Evaluation period not found.",
    ]);
    exit();
}
$periodStart = $evaluationPeriod["start_date"];
$periodEnd = $evaluationPeriod["end_date"];
$periodFilter = " AND (e.period_id = ? OR (
    e.period_id IS NULL
    AND DATE(e.created_at) BETWEEN ? AND ?
))";

/* ── fetch every subject+class for the selected closed period ──────────── */
/*
 * We query the evaluations table directly so we only surface subjects that
 * actually have evaluation data. The subject's program drives department
 * grouping; class IDs are used internally to keep ratings accurate.
 */
$subjectSql = "
    SELECT
        s.id                                                 AS subject_id,
        s.subject_code,
        s.subject_desc,
        s.semester,
        p.id                                                 AS program_id,
        p.program_code,
        p.program_name,
        e.class_id,
        COUNT(DISTINCT e.id)                                 AS eval_count,
        MIN(DATE(e.created_at))                              AS date_from,
        MAX(DATE(e.created_at))                              AS date_to
    FROM evaluations e
    INNER JOIN add_subjects s  ON s.id  = e.subject_id
    INNER JOIN add_programs p  ON p.id  = s.program_id
    WHERE e.faculty_id = ?
      AND e.subject_id > 0
      {$periodFilter}
    GROUP BY
        s.id,
        s.subject_code,
        s.subject_desc,
        s.semester,
        p.id,
        p.program_code,
        p.program_name,
        e.class_id
    ORDER BY p.program_name ASC, s.subject_code ASC
";

$subStmt = $conn->prepare($subjectSql);
$subStmt->bind_param("iiss", $numeric_id, $period_id, $periodStart, $periodEnd);
$subStmt->execute();
$subjectRows = $subStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$subStmt->close();

/* ── build per-department, per-subject structure ────────────────────────── */
$departments = []; // keyed by program_id

foreach ($subjectRows as $row) {
    $prog_id = intval($row["program_id"]);
    $subj_id = intval($row["subject_id"]);
    $class_id = intval($row["class_id"]);
    $eval_cnt = intval($row["eval_count"]);
    // Initialise department bucket
    if (!isset($departments[$prog_id])) {
        $departments[$prog_id] = [
            "program_id" => $prog_id,
            "program_code" => $row["program_code"],
            "program_name" => $row["program_name"],
            "subjects" => [],
        ];
    }

    // Weighted average for this subject+class
    $avg =
        $eval_cnt > 0
            ? calcWeightedScore(
                $conn,
                $numeric_id,
                $subj_id,
                $class_id,
                $period_id,
            )
            : 0.0;

    // Category breakdown for this subject+class
    $cats = getCategoryStats(
        $conn,
        $numeric_id,
        $subj_id,
        $class_id,
        $period_id,
    );
    $category_totals = [];
    foreach ($cats as $cat) {
        $category_totals[] = [
            "category_name" => $cat["category_name"],
            "avg_rating" => $cat["avg_rating"],
            "responses" => $cat["responses"],
            "weight" => $cat["weight"],
            "normalised_weight" => $cat["normalised_weight"],
        ];
    }

    // Student comments for this subject+class
    $comments = [];
    $fbStmt = $conn->prepare("
        SELECT feedback
        FROM evaluations
        WHERE faculty_id   = ?
          AND subject_id   = ?
          AND class_id     = ?
          AND (period_id = ? OR (
              period_id IS NULL
              AND DATE(created_at) BETWEEN ? AND ?
          ))
          AND feedback IS NOT NULL
          AND TRIM(feedback) != ''
        ORDER BY created_at DESC
    ");
    $fbStmt->bind_param(
        "iiiiss",
        $numeric_id,
        $subj_id,
        $class_id,
        $period_id,
        $periodStart,
        $periodEnd,
    );
    $fbStmt->execute();
    $fbRows = $fbStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $fbStmt->close();
    $comments = array_map(fn($r) => trim($r["feedback"]), $fbRows);

    // Period text
    $subjectPeriod = "N/A";
    if (!empty($row["date_from"]) && !empty($row["date_to"])) {
        $start = date("M j, Y", strtotime($row["date_from"]));
        $end = date("M j, Y", strtotime($row["date_to"]));
        $subjectPeriod = $start === $end ? $start : "$start – $end";
    }

    $departments[$prog_id]["subjects"][] = [
        "subject_id" => $subj_id,
        "subject_code" => $row["subject_code"],
        "subject_desc" => $row["subject_desc"],
        "semester" => $row["semester"],
        "eval_count" => $eval_cnt,
        "avg_rating" => number_format($avg, 2),
        "percentage" => number_format(($avg / 5) * 100, 2),
        "rating_label" => ratingLabel($avg),
        "rating_class" => ratingClass($avg),
        "period" => $subjectPeriod,
        "category_totals" => $category_totals,
        "comments" => $comments,
    ];
}

// Re-index as plain array for JSON
$departments = array_values($departments);

$conn->close();

echo json_encode([
    "success" => true,
    "period" => [
        "id" => intval($evaluationPeriod["id"]),
        "academic_year" => $evaluationPeriod["ay"],
        "semester" => $evaluationPeriod["semester"],
        "start_date" => $evaluationPeriod["start_date"],
        "end_date" => $evaluationPeriod["end_date"],
    ],
    "departments" => $departments,
]);
?>

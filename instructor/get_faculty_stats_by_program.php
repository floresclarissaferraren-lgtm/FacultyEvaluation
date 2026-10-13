<?php
require_once __DIR__ . '/../shared/security.php';
header("Content-Type: application/json");
include __DIR__ . '/../shared/connect.php';
require_once __DIR__ . '/../shared/evaluation_schema.php';
require_once __DIR__ . '/../shared/evaluation_period_helper.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'faculty' || !isset($_SESSION['id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$faculty_id = intval($_SESSION['id']);

// Check faculty status
$statusStmt = $conn->prepare("SELECT status FROM add_faculties WHERE id = ? LIMIT 1");
$statusStmt->bind_param("i", $faculty_id);
$statusStmt->execute();
$statusRow = $statusStmt->get_result()->fetch_assoc();
$statusStmt->close();

if (strtolower((string)($statusRow['status'] ?? 'active')) !== 'active') {
    echo json_encode([
        "success" => true,
        "inactive" => true,
        "message" => "Your account has been set to inactive by an admin.",
        "overall_rating" => "0.00",
        "rating_label" => "No Data",
        "total_responses" => 0
    ]);
    $conn->close();
    exit;
}

if (isEvaluationOngoing($conn)) {
    echo json_encode([
        "success" => true,
        "evaluation_ongoing" => true,
        "message" => "Evaluation results are unavailable while the evaluation process is still ongoing.",
        "overall_rating" => "0.00",
        "percentage_score" => "0.00",
        "rating_label" => "Unavailable",
        "total_responses" => 0,
        "programs_with_data" => []
    ]);
    $conn->close();
    exit;
}

require_once __DIR__ . '/../shared/weighted_score_helper.php';
ensureEvaluationsSchema($conn);

// Get optional program filter
$program_filter = isset($_GET['program']) ? trim($_GET['program']) : '';
$hasProgramFilter = $program_filter !== '' && $program_filter !== 'all';
$period_id = isset($_GET['period_id']) ? intval($_GET['period_id']) : 0;
if ($period_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Evaluation period is required."]);
    $conn->close();
    exit;
}

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
$periodStmt->bind_param("ii", $period_id, $faculty_id);
$periodStmt->execute();
$period = $periodStmt->get_result()->fetch_assoc();
$periodStmt->close();
if (!$period) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Evaluation period not found."]);
    $conn->close();
    exit;
}

$periodWhere = " AND (e.period_id = ? OR (
    e.period_id IS NULL
    AND DATE(e.created_at) BETWEEN ? AND ?
))";
$periodStart = $period['start_date'];
$periodEnd = $period['end_date'];

// Build WHERE clause
if ($hasProgramFilter) {
    // Use EXISTS so a matching program lookup cannot multiply response counts.
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total_responses,
               COUNT(DISTINCT NULLIF(e.subject_id, 0)) AS subject_count
        FROM evaluations e
        WHERE e.faculty_id = ?
          {$periodWhere}
          AND EXISTS (
              SELECT 1
              FROM add_students s
              INNER JOIN add_programs p
                  ON TRIM(p.program_code) = TRIM(s.program)
                  OR CAST(p.id AS CHAR) = TRIM(s.program)
              WHERE s.id = e.student_id
                AND p.program_name = ?
          )
    ");
    $stmt->bind_param("iisss", $faculty_id, $period_id, $periodStart, $periodEnd, $program_filter);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    $total = $row ? intval($row['total_responses']) : 0;

    $overall = $total > 0 ? calcWeightedScoreByProgram($conn, $faculty_id, $program_filter, null, null, $period_id) : 0.00;
} else {
    // No filter — use global stats
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total_responses,
               COUNT(DISTINCT NULLIF(e.subject_id, 0)) AS subject_count
        FROM evaluations e
        WHERE e.faculty_id = ? {$periodWhere}
    ");
    $stmt->bind_param("iiss", $faculty_id, $period_id, $periodStart, $periodEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    $row    = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    $total   = $row ? intval($row['total_responses']) : 0;
    $overall = $total > 0 ? calcWeightedScore($conn, $faculty_id, null, null, $period_id) : 0.00;
}

function ratingLabel(float $score, int $total): string {
    if ($total === 0) return "No Rating Yet";
    if ($score >= 4.5) return "Outstanding";
    if ($score >= 3.5) return "Very Good";
    if ($score >= 2.5) return "Good";
    if ($score >= 1.5) return "Fair";
    return "Needs Improvement";
}

$rating_label   = ratingLabel($overall, $total);
$display_rating = $total === 0 ? "0.00" : number_format($overall, 2);
$percentage     = $total === 0 ? "0.00" : number_format(($overall / 5) * 100, 2);
$category_totals = $hasProgramFilter
    ? getCategoryStatsByProgram($conn, $faculty_id, $program_filter, null, null, $period_id)
    : getCategoryStats($conn, $faculty_id, null, null, $period_id);

// Also fetch list of programs (with full names) that have evaluations for this faculty
$prog_stmt = $conn->prepare("
    SELECT DISTINCT
           COALESCE(p.program_name, s.program) AS program_name
    FROM evaluations e
    INNER JOIN add_students s ON s.id = e.student_id
    LEFT JOIN add_programs p ON TRIM(p.program_code) = TRIM(s.program)
                             OR CAST(p.id AS CHAR) = TRIM(s.program)
    WHERE e.faculty_id = ?
      {$periodWhere}
      AND s.program IS NOT NULL
      AND s.program != ''
    ORDER BY program_name ASC
");
$prog_stmt->bind_param("iiss", $faculty_id, $period_id, $periodStart, $periodEnd);
$prog_stmt->execute();
$prog_result = $prog_stmt->get_result();
$programs_with_data = [];
while ($prow = $prog_result->fetch_assoc()) {
    $name = trim($prow['program_name']);
    if ($name !== '') {
        $programs_with_data[] = [
            'code' => $name,
            'name' => $name,
        ];
    }
}
$prog_stmt->close();


echo json_encode([
    "success"            => true,
    "overall_rating"     => $display_rating,
    "percentage_score"   => $percentage,
    "rating_label"       => $rating_label,
    "total_responses"    => $total,
    "subject_count"      => intval($row['subject_count'] ?? 0),
    "category_totals"    => $category_totals,
    "programs_with_data" => $programs_with_data,
    "program_filter"     => $program_filter,
    "period"             => [
        "id" => intval($period['id']),
        "academic_year" => $period['ay'],
        "semester" => $period['semester'],
        "start_date" => $period['start_date'],
        "end_date" => $period['end_date']
    ]
]);

$conn->close();
?>

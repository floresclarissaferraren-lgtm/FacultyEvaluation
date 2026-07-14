<?php
include_once 'session_config.php';
session_start();
header("Content-Type: application/json");
include "connect.php";
require_once 'evaluation_schema.php';

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

require_once 'weighted_score_helper.php';
ensureEvaluationsSchema($conn);

// Get optional program filter
$program_filter = isset($_GET['program']) ? trim($_GET['program']) : '';

// Build WHERE clause
if ($program_filter !== '' && $program_filter !== 'all') {
    // Count total responses filtered by program name (via add_programs join)
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total_responses
        FROM evaluations e
        INNER JOIN add_students s ON s.id = e.student_id
        LEFT JOIN add_programs p ON TRIM(p.program_code) = TRIM(s.program)
                                 OR CAST(p.id AS CHAR) = TRIM(s.program)
        WHERE e.faculty_id = ?
          AND p.program_name = ?
    ");
    $stmt->bind_param("is", $faculty_id, $program_filter);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    $total = $row ? intval($row['total_responses']) : 0;

    $overall = $total > 0 ? calcWeightedScoreByProgram($conn, $faculty_id, $program_filter) : 0.00;
} else {
    // No filter — use global stats
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_responses FROM evaluations WHERE faculty_id = ?");
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row    = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    $total   = $row ? intval($row['total_responses']) : 0;
    $overall = $total > 0 ? calcWeightedScore($conn, $faculty_id) : 0.00;
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

// Also fetch list of programs (with full names) that have evaluations for this faculty
$prog_stmt = $conn->prepare("
    SELECT DISTINCT
           COALESCE(p.program_name, s.program) AS program_name
    FROM evaluations e
    INNER JOIN add_students s ON s.id = e.student_id
    LEFT JOIN add_programs p ON TRIM(p.program_code) = TRIM(s.program)
                             OR CAST(p.id AS CHAR) = TRIM(s.program)
    WHERE e.faculty_id = ?
      AND s.program IS NOT NULL
      AND s.program != ''
    ORDER BY program_name ASC
");
$prog_stmt->bind_param("i", $faculty_id);
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
    "programs_with_data" => $programs_with_data,
    "program_filter"     => $program_filter
]);

$conn->close();
?>

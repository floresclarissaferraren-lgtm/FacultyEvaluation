<?php
include_once 'session_config.php';
session_start();
header("Content-Type: application/json");
include "connect.php";
require_once 'evaluation_schema.php';
require_once 'evaluation_period_helper.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'faculty' || !isset($_SESSION['id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$faculty_id = intval($_SESSION['id']); // add_faculties.id

$statusStmt = $conn->prepare("SELECT status FROM add_faculties WHERE id = ? LIMIT 1");
$statusStmt->bind_param("i", $faculty_id);
$statusStmt->execute();
$statusRow = $statusStmt->get_result()->fetch_assoc();
$statusStmt->close();

if (strtolower((string)($statusRow['status'] ?? 'active')) !== 'active') {
    echo json_encode([
        "success" => true,
        "inactive" => true,
        "message" => "Your account has been set to inactive by an admin. You cannot generate or view result until your account is active again.",
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
        "total_responses" => 0
    ]);
    $conn->close();
    exit;
}

ensureEvaluationsSchema($conn);

require_once 'weighted_score_helper.php';

// Total responses
$stmt = $conn->prepare("SELECT COUNT(*) AS total_responses FROM evaluations WHERE faculty_id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
$row    = $result ? $result->fetch_assoc() : null;
$stmt->close();

$total   = $row ? intval($row['total_responses']) : 0;
$overall = $total > 0 ? calcWeightedScore($conn, $faculty_id) : 0.00;

function ratingLabel(float $score, int $total): string {
    // If no responses, return "No Rating Yet"
    if ($total === 0) {
        return "No Rating Yet";
    }
    
    if ($score >= 4.5) return "Outstanding";
    if ($score >= 3.5) return "Very Good";
    if ($score >= 2.5) return "Good";
    if ($score >= 1.5) return "Fair";
    return "Needs Improvement";
}

$rating_label   = ratingLabel($overall, $total);
$display_rating = $total === 0 ? "0.00" : number_format($overall, 2);
$percentage     = $total === 0 ? "0.00" : number_format(($overall / 5) * 100, 2);

echo json_encode([
    "success"          => true,
    "overall_rating"   => $display_rating,
    "percentage_score" => $percentage,
    "rating_label"     => $rating_label,
    "total_responses"  => $total
]);

$conn->close();
?>

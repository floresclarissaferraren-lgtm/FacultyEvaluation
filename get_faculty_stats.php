<?php
session_start();
header("Content-Type: application/json");
include "connect.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'faculty' || !isset($_SESSION['id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$faculty_id = intval($_SESSION['id']); // add_faculties.id

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

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_responses,
        ROUND(AVG(overall_rating), 2) AS overall_rating
    FROM evaluations
    WHERE faculty_id = ?
");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

$overall = $row && $row['overall_rating'] !== null ? floatval($row['overall_rating']) : 0.00;
$total = $row ? intval($row['total_responses']) : 0;

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

$rating_label = ratingLabel($overall, $total);
$display_rating = $total === 0 ? "0.00" : number_format($overall, 2);

echo json_encode([
    "success" => true,
    "overall_rating" => $display_rating,
    "rating_label" => $rating_label,
    "total_responses" => $total
]);

$conn->close();
?>

<?php
include "connect.php";

header("Content-Type: application/json");

$student_id = $_GET['student_id'] ?? 0;

if (!$student_id) {
    echo json_encode(["status" => "error", "message" => "Student ID is required"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT s.id, s.subject_code, s.subject_desc, s.year_level, p.program_name
    FROM student_subjects ss
    INNER JOIN add_subjects s ON ss.subject_id = s.id
    LEFT JOIN add_programs p ON s.program_id = p.id
    WHERE ss.student_id = ?
    ORDER BY s.year_level ASC, s.subject_code ASC
");

$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

echo json_encode([
    "status" => "success",
    "subjects" => $subjects,
    "count" => count($subjects)
]);

$stmt->close();
$conn->close();
?>

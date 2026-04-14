<?php
header("Content-Type: application/json");
require 'connect.php';

// Check if connection works
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$result = $conn->query("SELECT * FROM add_students ORDER BY id ASC");
if (!$result) {
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    exit;
}

$students = [];
while($row = $result->fetch_assoc()){
    // Get subjects for this student
    $student_id = $row['id'];
    $subject_query = "
        SELECT s.id, s.subject_code, s.subject_desc, s.year_level, p.program_name
        FROM student_subjects ss
        INNER JOIN add_subjects s ON ss.subject_id = s.id
        LEFT JOIN add_programs p ON s.program_id = p.id
        WHERE ss.student_id = ?
        ORDER BY s.subject_code ASC
    ";
    
    $stmt = $conn->prepare($subject_query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $subject_result = $stmt->get_result();
    
    $subjects = [];
    while($subject_row = $subject_result->fetch_assoc()){
        $subjects[] = $subject_row;
    }
    
    $row['subjects'] = $subjects;
    $students[] = $row;
    $stmt->close();
}

// Add debug info
$debug_info = [
    'total_students' => count($students),
    'query_success' => true
];

echo json_encode($students);
$conn->close();
?>

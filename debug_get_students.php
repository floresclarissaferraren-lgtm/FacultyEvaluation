<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
require 'connect.php';

echo "=== DEBUG: get_students.php ===\n";

try {
    // Test the exact query from get_students.php
    $result = $conn->query("SELECT * FROM add_students ORDER BY id ASC");
    
    if (!$result) {
        echo "Query failed: " . $conn->error . "\n";
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
        if (!$stmt) {
            echo "Prepare failed: " . $conn->error . "\n";
            continue;
        }
        
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
    
    echo "Found " . count($students) . " students\n";
    echo "JSON output:\n";
    echo json_encode($students, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>

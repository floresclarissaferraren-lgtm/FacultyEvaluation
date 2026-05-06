<?php
// Simple test file to verify faculty deletion
include "connect.php";

// Test data - check if faculty exists
$test_faculty_id = "FC-0002";
$stmt = $conn->prepare("SELECT * FROM add_faculties WHERE faculty_id = ?");
$stmt->bind_param("s", $test_faculty_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Faculty Deletion Test</h2>";

if ($result->num_rows > 0) {
    $faculty = $result->fetch_assoc();
    echo "<h3>Faculty Found:</h3>";
    echo "<pre>";
    print_r($faculty);
    echo "</pre>";
    
    // Check related records
    echo "<h3>Related Records:</h3>";
    
    // Check faculty_login
    $login_stmt = $conn->prepare("SELECT * FROM faculty_login WHERE faculty_id = ?");
    $login_stmt->bind_param("i", $faculty['id']);
    $login_stmt->execute();
    $login_result = $login_stmt->get_result();
    echo "<p>Faculty Login Records: " . $login_result->num_rows . "</p>";
    
    // Check class_subjects
    $class_stmt = $conn->prepare("SELECT * FROM class_subjects WHERE faculty_id = ?");
    $class_stmt->bind_param("i", $faculty['id']);
    $class_stmt->execute();
    $class_result = $class_stmt->get_result();
    echo "<p>Class Subject Records: " . $class_result->num_rows . "</p>";
    
    // Check evaluations
    $eval_stmt = $conn->prepare("SELECT * FROM evaluations WHERE faculty_id = ?");
    $eval_stmt->bind_param("i", $faculty['id']);
    $eval_stmt->execute();
    $eval_result = $eval_stmt->get_result();
    echo "<p>Evaluation Records: " . $eval_result->num_rows . "</p>";
    
    $login_stmt->close();
    $class_stmt->close();
    $eval_stmt->close();
    
} else {
    echo "<p>No faculty found with ID: $test_faculty_id</p>";
}

$stmt->close();
$conn->close();
?>

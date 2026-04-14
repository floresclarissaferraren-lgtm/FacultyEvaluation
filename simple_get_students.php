<?php
header("Content-Type: application/json");
require 'connect.php';

// Simple query without subjects first
$result = $conn->query("SELECT id, student_number, firstname, lastname, email, program, yearlevel FROM add_students ORDER BY id ASC");

if (!$result) {
    echo json_encode(["error" => "Query failed: " . $conn->error]);
    exit;
}

$students = [];
while($row = $result->fetch_assoc()){
    $students[] = $row;
}

echo json_encode($students);
$conn->close();
?>

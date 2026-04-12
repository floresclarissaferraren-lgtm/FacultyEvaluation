<?php
header("Content-Type: application/json");
require 'connect.php';

$result = $conn->query("SELECT * FROM add_students ORDER BY id ASC");
$students = [];
while($row = $result->fetch_assoc()){
    $students[] = $row;
}
echo json_encode($students);
$conn->close();
?>

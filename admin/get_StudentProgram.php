<?php
header("Content-Type: application/json");
require __DIR__ . '/../shared/connect.php'; // gamitin ang existing connection file mo

$result = $conn->query("SELECT id, program_name FROM add_programs ORDER BY program_name ASC");

$programs = [];
while($row = $result->fetch_assoc()){
    $programs[] = [
        'id' => $row['id'],
        'name' => $row['program_name']
    ]; 
}

echo json_encode($programs);
$conn->close();
?>

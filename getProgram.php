<?php
require_once 'security.php';
requireRole('admin');
include 'connect.php';

header('Content-Type: application/json'); 

$query = "SELECT id, program_code, program_name FROM add_programs ORDER BY id DESC";
$result = mysqli_query($conn, $query);

$programs = [];

if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $programs[] = $row;
    }
}

echo json_encode($programs);
exit;
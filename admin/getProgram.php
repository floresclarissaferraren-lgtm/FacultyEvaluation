<?php
require_once __DIR__ . '/../shared/security.php';
requireRole('admin');
include __DIR__ . '/../shared/connect.php';

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
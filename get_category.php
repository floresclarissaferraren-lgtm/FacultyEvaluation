<?php
header('Content-Type: application/json; charset=utf-8'); 

include "connect.php";

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

$sql = "SELECT id, category_name, section_number, COALESCE(weight, 0) AS weight FROM add_categories ORDER BY section_number ASC";
$result = $conn->query($sql);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
} else {
    echo json_encode(["success" => false, "message" => $conn->error]);
}
?>

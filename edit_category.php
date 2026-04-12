<?php
include 'connect.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'];
$category_name = $data['category_name'];
$section_number = $data['section_number'];

$sql = "UPDATE add_categories SET category_name=?, section_number=? WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $category_name, $section_number, $id);

$response = [];
$response['success'] = $stmt->execute();
echo json_encode($response);
?>

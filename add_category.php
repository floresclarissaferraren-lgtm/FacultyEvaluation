<?php
include 'connect.php';

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$category_name = isset($data['category_name']) ? trim($data['category_name']) : '';
$section_number = isset($data['section_number']) ? trim($data['section_number']) : '';

if ($category_name === '' || $section_number === '') {
    echo json_encode(["success"=>false,"message"=>"Missing fields"]);
    exit;
}

$sql = "INSERT INTO add_categories (category_name, section_number) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["success"=>false,"message"=>"Prepare failed: ".$conn->error]);
    exit;
}

// Kung section_number ay integer sa DB:
$section_number = (int)$section_number;
$stmt->bind_param("si", $category_name, $section_number);

$response = [];
if ($stmt->execute()) {
    $response['success'] = true;
    $response['id'] = $conn->insert_id;
} else {
    $response['success'] = false;
    $response['message'] = $conn->error;
}
echo json_encode($response);
?>

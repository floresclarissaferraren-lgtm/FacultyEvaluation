<?php
include 'connect.php';

$data = json_decode(file_get_contents("php://input"), true);
$category_id = $data['category_id'];
$question_text = $data['question_text'];

$sql = "INSERT INTO add_questions (category_id, question_text) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $category_id, $question_text);

$response = [];
if ($stmt->execute()) {
    $response['success'] = true;
    $response['id'] = $stmt->insert_id;
} else {
    $response['success'] = false;
    $response['message'] = $conn->error;
}
echo json_encode($response);
?>

<?php
include 'connect.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'];
$question_text = $data['question_text'];

$sql = "UPDATE add_questions SET question_text=? WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $question_text, $id);

$response = [];
$response['success'] = $stmt->execute();
echo json_encode($response);
?>

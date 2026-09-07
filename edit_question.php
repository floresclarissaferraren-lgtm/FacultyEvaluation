<?php
require_once 'security.php';
requireRole('admin');
requireMethod('POST');
include 'connect.php';

header("Content-Type: application/json");

// Block if a period is active
$periodCheck = $conn->query("SELECT id FROM evaluation_periods WHERE is_active = 1 LIMIT 1");
if ($periodCheck && $periodCheck->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Cannot edit questions while an evaluation period is active."]);
    exit;
}

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

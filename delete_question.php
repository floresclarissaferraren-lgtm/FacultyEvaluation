<?php
include 'connect.php';
header('Content-Type: application/json');

$id = isset($_POST['question_id']) && is_numeric($_POST['question_id']) ? (int)$_POST['question_id'] : null;

if (!$id) {
    echo json_encode(['success'=>false,'error'=>'Invalid or missing question_id']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM add_questions WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success'=>true,'deleted_id'=>$id]);
} else {
    echo json_encode(['success'=>false,'error'=>'Question not found']);
}

$stmt->close();
?>

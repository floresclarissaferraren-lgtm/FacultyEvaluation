<?php
require_once 'security.php';
requireRole('admin');
requireMethod('POST');
include 'connect.php';

header("Content-Type: application/json");

// Block if a period is active
$periodCheck = $conn->query("SELECT id FROM evaluation_periods WHERE is_active = 1 LIMIT 1");
if ($periodCheck && $periodCheck->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Cannot add questions while an evaluation period is active."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$category_id = $data['category_id'];
$question_text = $data['question_text'];

$count_sql = "SELECT COUNT(*) as question_count FROM add_questions WHERE category_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $category_id);
$count_stmt->execute();
$result = $count_stmt->get_result();
$row = $result->fetch_assoc();

if ($row['question_count'] >= 5) {
    $response['success'] = false;
    $response['message'] = "Maximum of 5 questions per category reached!";
    echo json_encode($response);
    exit;
}

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

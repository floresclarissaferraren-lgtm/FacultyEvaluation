<?php
include 'connect.php';
header('Content-Type: application/json');

// check kung may category_id
if (!isset($_GET['category_id'])) {
    echo json_encode(["error" => "Missing category_id"]);
    exit;
}

$category_id = intval($_GET['category_id']); // sanitize input
$sql = "SELECT id, question_text FROM add_questions WHERE category_id = $category_id";
$result = $conn->query($sql);

$questions = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
}

echo json_encode($questions);
$conn->close();
?>

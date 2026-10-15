<?php
require_once __DIR__ . "/../shared/security.php";
requireRole("admin");
requireMethod("POST");
include __DIR__ . "/../shared/connect.php";

header("Content-Type: application/json");

// Block if a period is active
$periodCheck = $conn->query(
    "SELECT id FROM evaluation_periods WHERE is_active = 1 LIMIT 1",
);
if ($periodCheck && $periodCheck->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" =>
            "Cannot add questions while an evaluation period is active.",
    ]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$category_id = intval($data["category_id"] ?? 0);
$question_text = trim((string) ($data["question_text"] ?? ""));

$duplicate_sql = "SELECT id FROM add_questions
                  WHERE category_id = ? AND LOWER(TRIM(question_text)) = LOWER(TRIM(?))
                  LIMIT 1";
$duplicate_stmt = $conn->prepare($duplicate_sql);
$duplicate_stmt->bind_param("is", $category_id, $question_text);
$duplicate_stmt->execute();
$duplicate_result = $duplicate_stmt->get_result();
if ($duplicate_result->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "This question already exists in this category.",
    ]);
    exit();
}

$count_sql =
    "SELECT COUNT(*) as question_count FROM add_questions WHERE category_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $category_id);
$count_stmt->execute();
$result = $count_stmt->get_result();
$row = $result->fetch_assoc();

if ($row["question_count"] >= 5) {
    $response["success"] = false;
    $response["message"] = "Maximum of 5 questions per category reached!";
    echo json_encode($response);
    exit();
}

$sql = "INSERT INTO add_questions (category_id, question_text) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $category_id, $question_text);

$response = [];
if ($stmt->execute()) {
    $response["success"] = true;
    $response["id"] = $stmt->insert_id;
} else {
    $response["success"] = false;
    $response["message"] = $conn->error;
}
echo json_encode($response);
?>

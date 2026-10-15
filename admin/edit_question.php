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
            "Cannot edit questions while an evaluation period is active.",
    ]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data["id"] ?? 0);
$category_id = intval($data["category_id"] ?? 0);
$question_text = trim((string) ($data["question_text"] ?? ""));

$duplicate_sql = "SELECT id FROM add_questions
                  WHERE category_id = ? AND id <> ?
                    AND LOWER(TRIM(question_text)) = LOWER(TRIM(?))
                  LIMIT 1";
$duplicate_stmt = $conn->prepare($duplicate_sql);
$duplicate_stmt->bind_param("iis", $category_id, $id, $question_text);
$duplicate_stmt->execute();
$duplicate_result = $duplicate_stmt->get_result();
if ($duplicate_result->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "This question already exists in this category.",
    ]);
    exit();
}

$sql = "UPDATE add_questions SET question_text=? WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $question_text, $id);

$response = [];
$response["success"] = $stmt->execute();
echo json_encode($response);
?>

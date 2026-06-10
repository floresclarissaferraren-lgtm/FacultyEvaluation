<?php
include 'connect.php';

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$category_name  = isset($data['category_name'])  ? trim($data['category_name'])  : '';
$section_number = isset($data['section_number'])  ? trim($data['section_number']) : '';
$weight         = isset($data['weight'])          ? floatval($data['weight'])     : 0.00;

if ($category_name === '' || $section_number === '') {
    echo json_encode(["success"=>false,"message"=>"Missing fields"]);
    exit;
}

// Ensure weight column exists (idempotent guard)
$conn->query("ALTER TABLE add_categories ADD COLUMN IF NOT EXISTS weight DECIMAL(5,2) NOT NULL DEFAULT 0.00");

$section_number = (int)$section_number;

// --- Server-side weight validation (only when a non-zero weight is supplied) ---
if ($weight > 0) {
    $sumRes = $conn->query("SELECT COALESCE(SUM(weight), 0) AS total FROM add_categories");
    $sumRow = $sumRes ? $sumRes->fetch_assoc() : null;
    $currentTotal = $sumRow ? round(floatval($sumRow['total']), 2) : 0.0;

    if (round($currentTotal + $weight, 2) > 100.01) {   // 0.01 tolerance for float rounding
        $available = round(100 - $currentTotal, 2);
        echo json_encode([
            "success"   => false,
            "message"   => "Weight exceeds 100%. Other categories already use {$currentTotal}%. Maximum you can assign is {$available}%."
        ]);
        exit;
    }
}

$sql  = "INSERT INTO add_categories (category_name, section_number, weight) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["success"=>false,"message"=>"Prepare failed: ".$conn->error]);
    exit;
}

$stmt->bind_param("sid", $category_name, $section_number, $weight);

$response = [];
if ($stmt->execute()) {
    $response['success'] = true;
    $response['id']      = $conn->insert_id;
} else {
    $response['success'] = false;
    $response['message'] = $conn->error;
}
echo json_encode($response);
?>

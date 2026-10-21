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
            "Cannot add categories while an evaluation period is active.",
    ]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$category_name = isset($data["category_name"])
    ? trim($data["category_name"])
    : "";
$section_number = isset($data["section_number"])
    ? trim($data["section_number"])
    : "";
$weight = isset($data["weight"]) ? floatval($data["weight"]) : 0.0;

if ($category_name === "" || $section_number === "") {
    echo json_encode([
        "success" => false,
        "message" => "Missing fields",
    ]);
    exit();
}

$section_number = (int) $section_number;

$duplicateStmt = $conn->prepare("
    SELECT id FROM add_categories
    WHERE LOWER(TRIM(category_name)) = LOWER(TRIM(?))
    LIMIT 1
");
$duplicateStmt->bind_param("s", $category_name);
$duplicateStmt->execute();
if ($duplicateStmt->get_result()->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "This category already exists.",
    ]);
    exit();
}
$duplicateStmt->close();

$sectionStmt = $conn->prepare(
    "SELECT id FROM add_categories WHERE section_number = ? LIMIT 1",
);
$sectionStmt->bind_param("i", $section_number);
$sectionStmt->execute();
if ($sectionStmt->get_result()->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "This section number is already in use.",
    ]);
    exit();
}
$sectionStmt->close();

// Ensure weight column exists (idempotent guard)
$conn->query(
    "ALTER TABLE add_categories ADD COLUMN IF NOT EXISTS weight DECIMAL(5,2) NOT NULL DEFAULT 0.00",
);

// --- Server-side weight validation (only when a non-zero weight is supplied) ---
if ($weight > 0) {
    $sumRes = $conn->query(
        "SELECT COALESCE(SUM(weight), 0) AS total FROM add_categories",
    );
    $sumRow = $sumRes ? $sumRes->fetch_assoc() : null;
    $currentTotal = $sumRow ? round(floatval($sumRow["total"]), 2) : 0.0;

    if (round($currentTotal + $weight, 2) > 100.01) {
        // 0.01 tolerance for float rounding
        $available = round(100 - $currentTotal, 2);
        echo json_encode([
            "success" => false,
            "message" => "Weight exceeds 100%. Other categories already use {$currentTotal}%. Maximum you can assign is {$available}%.",
        ]);
        exit();
    }
}

$sql =
    "INSERT INTO add_categories (category_name, section_number, weight) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Prepare failed: " . $conn->error,
    ]);
    exit();
}

$stmt->bind_param("sid", $category_name, $section_number, $weight);

$response = [];
try {
    if ($stmt->execute()) {
        $response["success"] = true;
        $response["id"] = $conn->insert_id;
    }
} catch (mysqli_sql_exception $exception) {
    if ($exception->getCode() !== 1062) {
        throw $exception;
    }

    $response["success"] = false;
    $response["message"] =
        "This category or section number already exists.";
}
echo json_encode($response);
?>

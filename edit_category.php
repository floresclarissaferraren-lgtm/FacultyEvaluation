<?php
include 'connect.php';

$data           = json_decode(file_get_contents("php://input"), true);
$id             = intval($data['id'] ?? 0);
$category_name  = trim($data['category_name'] ?? '');
$section_number = intval($data['section_number'] ?? 0);
$weight         = isset($data['weight']) ? floatval($data['weight']) : null;

if (!$id || $category_name === '') {
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit;
}

// Ensure weight column exists (idempotent guard)
$conn->query("ALTER TABLE add_categories ADD COLUMN IF NOT EXISTS weight DECIMAL(5,2) NOT NULL DEFAULT 0.00");

// --- Server-side weight validation ---
if ($weight !== null && $weight > 0) {
    // Sum of all weights EXCLUDING this category
    $sumStmt = $conn->prepare("SELECT COALESCE(SUM(weight), 0) AS total FROM add_categories WHERE id != ?");
    $sumStmt->bind_param("i", $id);
    $sumStmt->execute();
    $sumRow = $sumStmt->get_result()->fetch_assoc();
    $sumStmt->close();
    $othersTotal = round(floatval($sumRow['total']), 2);

    if (round($othersTotal + $weight, 2) > 100.01) {
        $available = round(100 - $othersTotal, 2);
        echo json_encode([
            'success' => false,
            'message' => "Weight exceeds 100%. Other categories already use {$othersTotal}%. Maximum you can assign here is {$available}%."
        ]);
        exit;
    }
}

if ($weight !== null) {
    $sql  = "UPDATE add_categories SET category_name=?, section_number=?, weight=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdi", $category_name, $section_number, $weight, $id);
} else {
    $sql  = "UPDATE add_categories SET category_name=?, section_number=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $category_name, $section_number, $id);
}

$response = [];
$response['success'] = $stmt->execute();
if (!$response['success']) {
    $response['message'] = $conn->error;
}
echo json_encode($response);
?>

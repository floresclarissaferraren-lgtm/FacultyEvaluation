<?php
/**
 * One-time migration: adds the `weight` column to add_categories.
 * Safe to run multiple times (uses IF NOT EXISTS logic).
 */
include 'connect.php';

$results = [];

// Add weight column if missing
$check = $conn->query("SHOW COLUMNS FROM add_categories LIKE 'weight'");
if ($check && $check->num_rows === 0) {
    $conn->query("ALTER TABLE add_categories ADD COLUMN weight DECIMAL(5,2) NOT NULL DEFAULT 0.00");
    $results[] = "Column `weight` added to add_categories.";
} else {
    $results[] = "Column `weight` already exists — skipped.";
}

// Set equal default weights for any rows that still have 0
$countRes = $conn->query("SELECT COUNT(*) AS n FROM add_categories");
$countRow = $countRes->fetch_assoc();
$n = intval($countRow['n']);

if ($n > 0) {
    $zeroRes = $conn->query("SELECT COUNT(*) AS z FROM add_categories WHERE weight = 0");
    $zeroRow = $zeroRes->fetch_assoc();
    if (intval($zeroRow['z']) > 0) {
        $equalWeight = round(100 / $n, 2);
        $conn->query("UPDATE add_categories SET weight = {$equalWeight} WHERE weight = 0");
        $results[] = "Assigned equal default weight ({$equalWeight}%) to {$n} categories with weight=0.";
    } else {
        $results[] = "All categories already have weights assigned.";
    }
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'messages' => $results]);
$conn->close();
?>

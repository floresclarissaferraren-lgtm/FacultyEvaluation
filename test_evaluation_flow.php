<?php
/**
 * Test Evaluation Flow
 * This page tests the complete flow from activating a period to allowing student evaluations
 */

include 'connect.php';
require_once 'evaluation_period_helper.php';

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Test Evaluation Flow</title>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .test-section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #1e3a8a; }
    h2 { color: #3b82f6; margin-top: 0; }
    .status { padding: 8px 12px; border-radius: 4px; display: inline-block; margin: 5px 0; }
    .success { background: #d1fae5; color: #065f46; }
    .error { background: #fee2e2; color: #991b1b; }
    .warning { background: #fef3c7; color: #92400e; }
    .info { background: #dbeafe; color: #1e40af; }
    table { border-collapse: collapse; width: 100%; margin-top: 10px; }
    th, td { border: 1px solid #e5e7eb; padding: 12px; text-align: left; }
    th { background: #f3f4f6; font-weight: 600; }
    .highlight { background: #fef3c7; font-weight: bold; }
</style>";
echo "</head><body>";

echo "<h1>🧪 Evaluation Flow Test</h1>";
echo "<p>Testing if students can evaluate when a period is activated.</p>";

// Test 1: Check evaluation_settings
echo "<div class='test-section'>";
echo "<h2>Test 1: Evaluation Settings Status</h2>";

$settingsRes = $conn->query("SELECT evaluation_open, active_period_id FROM evaluation_settings WHERE id = 1 LIMIT 1");
$settings = $settingsRes ? $settingsRes->fetch_assoc() : null;

if ($settings) {
    $evaluationOpen = intval($settings["evaluation_open"]) === 1;
    $activePeriodId = intval($settings["active_period_id"] ?? 0);
    
    echo "<table>";
    echo "<tr><th>Property</th><th>Value</th><th>Status</th></tr>";
    echo "<tr>";
    echo "<td><strong>evaluation_open</strong></td>";
    echo "<td>" . ($evaluationOpen ? "1 (TRUE)" : "0 (FALSE)") . "</td>";
    echo "<td><span class='status " . ($evaluationOpen ? "success" : "error") . "'>" . ($evaluationOpen ? "✅ OPEN" : "❌ CLOSED") . "</span></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td><strong>active_period_id</strong></td>";
    echo "<td>" . ($activePeriodId > 0 ? $activePeriodId : "NULL") . "</td>";
    echo "<td><span class='status " . ($activePeriodId > 0 ? "success" : "warning") . "'>" . ($activePeriodId > 0 ? "✅ Set" : "⚠️ Not Set") . "</span></td>";
    echo "</tr>";
    echo "</table>";
    
    if ($evaluationOpen && $activePeriodId > 0) {
        echo "<p class='status success'>✅ <strong>Students CAN EVALUATE</strong> - Evaluation is open with an active period!</p>";
    } else {
        echo "<p class='status error'>❌ <strong>Students CANNOT EVALUATE</strong> - Evaluation is closed or no active period.</p>";
    }
} else {
    echo "<p class='status error'>❌ No settings found!</p>";
}
echo "</div>";

// Test 2: Check active period details
echo "<div class='test-section'>";
echo "<h2>Test 2: Active Period Details</h2>";

if ($activePeriodId > 0) {
    $periodStmt = $conn->prepare("SELECT ay, semester, start_date, end_date, is_active FROM evaluation_periods WHERE id = ? LIMIT 1");
    $periodStmt->bind_param("i", $activePeriodId);
    $periodStmt->execute();
    $period = $periodStmt->get_result()->fetch_assoc();
    $periodStmt->close();
    
    if ($period) {
        $today = date("Y-m-d");
        $inDuration = ($period["start_date"] <= $today && $period["end_date"] >= $today);
        
        echo "<table>";
        echo "<tr><th>Property</th><th>Value</th></tr>";
        echo "<tr><td><strong>Academic Year</strong></td><td>" . htmlspecialchars($period["ay"]) . "</td></tr>";
        echo "<tr><td><strong>Semester</strong></td><td>" . htmlspecialchars($period["semester"]) . "</td></tr>";
        echo "<tr><td><strong>Start Date</strong></td><td>" . ($period["start_date"] ?? "NULL") . "</td></tr>";
        echo "<tr><td><strong>End Date</strong></td><td>" . ($period["end_date"] ?? "NULL") . "</td></tr>";
        echo "<tr><td><strong>is_active</strong></td><td>" . ($period["is_active"] ? "1 (TRUE)" : "0 (FALSE)") . "</td></tr>";
        echo "<tr><td><strong>Today's Date</strong></td><td>" . $today . "</td></tr>";
        echo "<tr class='highlight'><td><strong>In Duration?</strong></td><td>" . ($inDuration ? "✅ YES" : "❌ NO") . "</td></tr>";
        echo "</table>";
        
        if ($period["is_active"] && $inDuration) {
            echo "<p class='status success'>✅ Period is active and within duration!</p>";
        } else if (!$period["is_active"]) {
            echo "<p class='status error'>❌ Period is marked as inactive!</p>";
        } else if (!$inDuration) {
            echo "<p class='status warning'>⚠️ Period is active but outside duration!</p>";
        }
    } else {
        echo "<p class='status error'>❌ Active period ID exists but period not found in database!</p>";
    }
} else {
    echo "<p class='status warning'>⚠️ No active period set.</p>";
}
echo "</div>";

// Test 3: Check isEvaluationOngoing() function
echo "<div class='test-section'>";
echo "<h2>Test 3: isEvaluationOngoing() Function</h2>";

$isOngoing = isEvaluationOngoing($conn);
echo "<p>Function result: <strong>" . ($isOngoing ? "TRUE" : "FALSE") . "</strong></p>";
echo "<p class='status " . ($isOngoing ? "success" : "error") . "'>";
echo $isOngoing ? "✅ Students CAN evaluate (function returns TRUE)" : "❌ Students CANNOT evaluate (function returns FALSE)";
echo "</p>";
echo "</div>";

// Test 4: List all periods
echo "<div class='test-section'>";
echo "<h2>Test 4: All Evaluation Periods</h2>";

$allPeriods = $conn->query("SELECT id, ay, semester, start_date, end_date, is_active FROM evaluation_periods ORDER BY created_at DESC");

if ($allPeriods && $allPeriods->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Academic Year</th><th>Semester</th><th>Start Date</th><th>End Date</th><th>is_active</th></tr>";
    
    while ($p = $allPeriods->fetch_assoc()) {
        $rowClass = intval($p["id"]) === $activePeriodId ? " class='highlight'" : "";
        echo "<tr{$rowClass}>";
        echo "<td>" . htmlspecialchars($p["id"]) . "</td>";
        echo "<td>" . htmlspecialchars($p["ay"]) . "</td>";
        echo "<td>" . htmlspecialchars($p["semester"]) . "</td>";
        echo "<td>" . ($p["start_date"] ?? "<em style='color:#f59e0b;'>NULL</em>") . "</td>";
        echo "<td>" . ($p["end_date"] ?? "<em style='color:#f59e0b;'>NULL</em>") . "</td>";
        echo "<td>" . ($p["is_active"] ? "✅" : "❌") . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<p><em>Highlighted row (if any) is the currently active period.</em></p>";
} else {
    echo "<p class='status warning'>⚠️ No periods found in database.</p>";
}
echo "</div>";

// Test 5: Check API endpoint
echo "<div class='test-section'>";
echo "<h2>Test 5: API Endpoint Check</h2>";
echo "<p>Testing <code>periods_api.php?action=status</code> endpoint...</p>";

$apiUrl = "periods_api.php?action=status";
$apiResponse = @file_get_contents($apiUrl);

if ($apiResponse) {
    $apiData = json_decode($apiResponse, true);
    
    if ($apiData && isset($apiData['success'])) {
        echo "<pre style='background:#f3f4f6; padding:10px; border-radius:4px; overflow-x:auto;'>";
        echo json_encode($apiData, JSON_PRETTY_PRINT);
        echo "</pre>";
        
        if ($apiData['success'] && $apiData['evaluation_open']) {
            echo "<p class='status success'>✅ API confirms: Evaluation is OPEN</p>";
        } else {
            echo "<p class='status error'>❌ API confirms: Evaluation is CLOSED</p>";
        }
    } else {
        echo "<p class='status error'>❌ Invalid API response format</p>";
    }
} else {
    echo "<p class='status error'>❌ Failed to fetch API response</p>";
}
echo "</div>";

// Summary
echo "<div class='test-section'>";
echo "<h2>📊 Summary</h2>";

$canEvaluate = $evaluationOpen && $activePeriodId > 0 && $isOngoing;

if ($canEvaluate) {
    echo "<h3 style='color:#065f46;'>✅ SYSTEM STATUS: STUDENTS CAN EVALUATE</h3>";
    echo "<p>All checks passed! Students should be able to access the evaluation page and submit evaluations.</p>";
} else {
    echo "<h3 style='color:#991b1b;'>❌ SYSTEM STATUS: STUDENTS CANNOT EVALUATE</h3>";
    echo "<p><strong>Reasons:</strong></p>";
    echo "<ul>";
    if (!$evaluationOpen) echo "<li>evaluation_open is set to FALSE</li>";
    if ($activePeriodId <= 0) echo "<li>No active period is set</li>";
    if (!$isOngoing) echo "<li>isEvaluationOngoing() function returns FALSE</li>";
    echo "</ul>";
    echo "<p><strong>To fix:</strong> Go to Academic Year Management → Manage Periods → Click 'Inactive' button to activate a period with valid dates.</p>";
}
echo "</div>";

echo "</body></html>";

$conn->close();
?>

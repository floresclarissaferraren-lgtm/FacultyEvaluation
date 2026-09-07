<?php
// Debug script to check period status
header("Content-Type: text/html; charset=UTF-8");
include "connect.php";
require_once 'evaluation_period_helper.php';

echo "<h2>Period Status Debug</h2>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #4CAF50; color: white; }
tr:nth-child(even) { background-color: #f2f2f2; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.info { color: blue; font-weight: bold; }
</style>";

$today = date("Y-m-d");
echo "<p><strong>Current Date:</strong> <span class='info'>$today</span></p>";

// Check evaluation_settings
echo "<h3>1. Evaluation Settings Table</h3>";
$settingsRes = $conn->query("SELECT * FROM evaluation_settings WHERE id = 1 LIMIT 1");
if ($settingsRes && $settingsRes->num_rows > 0) {
    $settings = $settingsRes->fetch_assoc();
    echo "<table>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    foreach ($settings as $key => $value) {
        $displayValue = $value === null ? '<em>NULL</em>' : htmlspecialchars($value);
        echo "<tr><td>$key</td><td>$displayValue</td></tr>";
    }
    echo "</table>";
    
    $evaluationOpen = intval($settings["evaluation_open"]) === 1;
    $activePeriodId = intval($settings["active_period_id"] ?? 0);
    
    echo "<p><strong>Evaluation Open:</strong> <span class='" . ($evaluationOpen ? "success" : "error") . "'>" . ($evaluationOpen ? "YES" : "NO") . "</span></p>";
    echo "<p><strong>Active Period ID:</strong> <span class='info'>$activePeriodId</span></p>";
} else {
    echo "<p class='error'>No settings found!</p>";
}

// Check active period
echo "<h3>2. Active Evaluation Period</h3>";
if (isset($activePeriodId) && $activePeriodId > 0) {
    $stmt = $conn->prepare("SELECT * FROM evaluation_periods WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $activePeriodId);
    $stmt->execute();
    $period = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($period) {
        echo "<table>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($period as $key => $value) {
            $displayValue = $value === null ? '<em>NULL</em>' : htmlspecialchars($value);
            echo "<tr><td>$key</td><td>$displayValue</td></tr>";
        }
        echo "</table>";
        
        $startDate = $period["start_date"] ?? null;
        $endDate = $period["end_date"] ?? null;
        
        if ($startDate && $endDate) {
            $startOk = $startDate <= $today;
            $endOk = $endDate >= $today;
            $inDuration = $startOk && $endOk;
            
            echo "<h4>Period Validation:</h4>";
            echo "<p><strong>Start Date Check:</strong> $startDate <= $today = <span class='" . ($startOk ? "success" : "error") . "'>" . ($startOk ? "PASS" : "FAIL") . "</span></p>";
            echo "<p><strong>End Date Check:</strong> $endDate >= $today = <span class='" . ($endOk ? "success" : "error") . "'>" . ($endOk ? "PASS" : "FAIL") . "</span></p>";
            echo "<p><strong>Within Duration:</strong> <span class='" . ($inDuration ? "success" : "error") . "'>" . ($inDuration ? "YES" : "NO") . "</span></p>";
        } else {
            echo "<p class='error'>Start date or end date is NULL!</p>";
        }
    } else {
        echo "<p class='error'>Period not found!</p>";
    }
} else {
    echo "<p class='error'>No active period ID set!</p>";
}

// Check what periods_api.php returns
echo "<h3>3. API Response (periods_api.php?action=status)</h3>";
$apiUrl = "periods_api.php?action=status";
echo "<p><a href='$apiUrl' target='_blank'>Click here to view raw API response</a></p>";

// Test isEvaluationOngoing function
echo "<h3>4. isEvaluationOngoing() Function Test</h3>";
$isOngoing = isEvaluationOngoing($conn);
echo "<p><strong>Result:</strong> <span class='" . ($isOngoing ? "success" : "error") . "'>" . ($isOngoing ? "TRUE (Evaluation is ongoing)" : "FALSE (Evaluation is closed)") . "</span></p>";

// List all periods
echo "<h3>5. All Evaluation Periods</h3>";
$allPeriods = $conn->query("SELECT * FROM evaluation_periods ORDER BY id DESC");
if ($allPeriods && $allPeriods->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>AY</th><th>Semester</th><th>Start Date</th><th>End Date</th><th>Active</th><th>Status</th></tr>";
    while ($row = $allPeriods->fetch_assoc()) {
        $isActive = intval($row["is_active"]) === 1;
        $start = $row["start_date"] ?? null;
        $end = $row["end_date"] ?? null;
        
        $status = "N/A";
        if ($start && $end) {
            if ($today < $start) {
                $status = "<span style='color: orange;'>Not Started</span>";
            } elseif ($today > $end) {
                $status = "<span style='color: red;'>Expired</span>";
            } else {
                $status = "<span style='color: green;'>Active Duration</span>";
            }
        } elseif (!$start || !$end) {
            $status = "<span style='color: gray;'>No Dates Set</span>";
        }
        
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['ay']}</td>";
        echo "<td>{$row['semester']}</td>";
        echo "<td>" . ($start ?: "<em>NULL</em>") . "</td>";
        echo "<td>" . ($end ?: "<em>NULL</em>") . "</td>";
        echo "<td>" . ($isActive ? "<span class='success'>YES</span>" : "NO") . "</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>No periods found!</p>";
}

$conn->close();
?>

<?php
// Quick test without session
header("Content-Type: application/json");
include "connect.php";

$today = date("Y-m-d");

// Check settings
$settingsRes = $conn->query("SELECT evaluation_open, active_period_id FROM evaluation_settings WHERE id = 1 LIMIT 1");
$settings = $settingsRes ? $settingsRes->fetch_assoc() : null;

$output = [
    "today" => $today,
    "settings_found" => $settings !== null,
    "evaluation_open_raw" => $settings ? $settings["evaluation_open"] : null,
    "evaluation_open_bool" => $settings ? (intval($settings["evaluation_open"]) === 1) : false,
    "active_period_id" => $settings ? intval($settings["active_period_id"] ?? 0) : 0,
];

// Check active period
$activePeriodId = $output["active_period_id"];
if ($activePeriodId > 0) {
    $stmt = $conn->prepare("SELECT ay, semester, start_date, end_date FROM evaluation_periods WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $activePeriodId);
    $stmt->execute();
    $period = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($period) {
        $output["period"] = $period;
        $output["start_check"] = $period["start_date"] . " <= " . $today . " = " . ($period["start_date"] <= $today ? "PASS" : "FAIL");
        $output["end_check"] = $period["end_date"] . " >= " . $today . " = " . ($period["end_date"] >= $today ? "PASS" : "FAIL");
        $output["in_duration"] = ($period["start_date"] <= $today && $period["end_date"] >= $today);
        $output["final_evaluation_open"] = $output["evaluation_open_bool"] && $output["in_duration"];
    } else {
        $output["period"] = null;
        $output["error"] = "Period not found";
        $output["final_evaluation_open"] = false;
    }
} else {
    $output["period"] = null;
    $output["error"] = "No active period ID";
    $output["final_evaluation_open"] = false;
}

echo json_encode($output, JSON_PRETTY_PRINT);
$conn->close();
?>

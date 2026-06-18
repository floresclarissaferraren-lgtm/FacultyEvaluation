<?php
include_once 'session_config.php'; // Load session settings BEFORE session_start
session_start();

header("Content-Type: application/json");

// Refresh session activity
if (isset($_SESSION['role'])) {
    // Update session last activity time
    $_SESSION['last_activity'] = time();
    
    echo json_encode([
        "success" => true,
        "status" => "active",
        "role" => $_SESSION['role'],
        "timestamp" => date("Y-m-d H:i:s")
    ]);
} else {
    // Session expired or not logged in
    echo json_encode([
        "success" => false,
        "status" => "expired",
        "message" => "Session has expired"
    ]);
}
?>

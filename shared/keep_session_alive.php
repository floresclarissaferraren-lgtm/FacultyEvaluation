<?php
require_once __DIR__ . "/security.php";

header("Content-Type: application/json");

// Refresh session activity
if (isset($_SESSION["role"])) {
    // Update session last activity time
    $_SESSION["last_activity"] = time();

    echo json_encode([
        "success" => true,
        "status" => "active",
        "role" => $_SESSION["role"],
        "timestamp" => date("Y-m-d H:i:s"),
    ]);
} else {
    // Session expired or not logged in
    echo json_encode([
        "success" => false,
        "status" => "expired",
        "message" => "Session has expired",
    ]);
}
?>

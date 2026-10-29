<?php
require_once __DIR__ . "/security.php";

header("Content-Type: application/json");

// Refresh session activity
if (isset($_SESSION["role"])) {
    $role = (string) $_SESSION["role"];
    // Update session last activity time
    $_SESSION["last_activity"] = time();

    // Persist the activity update and release PHP's per-session lock before
    // sending the response so other requests from this account can continue.
    session_write_close();

    echo json_encode([
        "success" => true,
        "status" => "active",
        "role" => $role,
        "timestamp" => date("Y-m-d H:i:s"),
    ]);
} else {
    session_write_close();

    // Session expired or not logged in
    echo json_encode([
        "success" => false,
        "status" => "expired",
        "message" => "Session has expired",
    ]);
}
?>

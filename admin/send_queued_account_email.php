<?php
require_once __DIR__ . "/../shared/security.php";
requireRole("admin");
requireMethod("POST");
header("Content-Type: application/json");
require_once __DIR__ . "/../shared/mailer.php";

$data = json_decode(file_get_contents("php://input"), true);
$token = is_array($data) ? (string) ($data["token"] ?? "") : "";

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid or expired email request.",
    ]);
    exit();
}

$pendingEmails = $_SESSION["pending_account_emails"] ?? [];
$email = is_array($pendingEmails) ? ($pendingEmails[$token] ?? null) : null;
if (
    !is_array($email) ||
    intval($email["created_at"] ?? 0) < time() - 600
) {
    http_response_code(410);
    echo json_encode([
        "success" => false,
        "message" => "Email request expired. Use Forgot Password if credentials were not received.",
    ]);
    exit();
}

unset($_SESSION["pending_account_emails"][$token]);
session_write_close();

$sent = sendEmail(
    $email["to_email"],
    $email["to_name"],
    $email["subject"],
    $email["body"],
);

if (!$sent) {
    http_response_code(502);
    echo json_encode([
        "success" => false,
        "message" => "The account was saved, but the welcome email could not be sent. Use Forgot Password if credentials were not received.",
    ]);
    exit();
}

echo json_encode([
    "success" => true,
    "message" => "Welcome email sent successfully.",
]);

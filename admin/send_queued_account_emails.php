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
$batch = is_array($pendingEmails) ? ($pendingEmails[$token] ?? null) : null;
if (
    !is_array($batch) ||
    !is_array($batch["emails"] ?? null) ||
    intval($batch["created_at"] ?? 0) < time() - 600
) {
    http_response_code(410);
    echo json_encode([
        "success" => false,
        "message" => "Email request expired.",
    ]);
    exit();
}

unset($_SESSION["pending_account_emails"][$token]);
session_write_close();

$sent = 0;
$failed = 0;
$warnings = [];
foreach ($batch["emails"] as $email) {
    if (
        !is_array($email) ||
        !filter_var($email["to_email"] ?? "", FILTER_VALIDATE_EMAIL) ||
        !isset($email["to_name"], $email["subject"], $email["body"])
    ) {
        $failed++;
        $warnings[] = [
            "row" => intval($email["row"] ?? 0),
            "student_number" => (string) ($email["student_number"] ?? ""),
        ];
        continue;
    }

    $emailSent = sendEmail(
        $email["to_email"],
        $email["to_name"],
        $email["subject"],
        $email["body"],
        null,
        null,
        [],
        true,
    );
    if ($emailSent) {
        $sent++;
        continue;
    }

    $failed++;
    $warnings[] = [
        "row" => intval($email["row"] ?? 0),
        "student_number" => (string) ($email["student_number"] ?? ""),
    ];
    error_log(
        "Bulk student welcome email failed on CSV row " .
            intval($email["row"] ?? 0) .
            ": " .
            getLastEmailError(),
    );
}

echo json_encode([
    "success" => true,
    "email_sent" => $sent,
    "email_failed" => $failed,
    "email_warnings" => $warnings,
]);

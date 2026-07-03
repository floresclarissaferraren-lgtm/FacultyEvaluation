<?php
header('Content-Type: application/json; charset=utf-8');
include 'connect.php';
include 'mailer.php';

$email = trim($_POST['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Look up the user — students and faculty only (admin has no email column)
$found = false;
$recipientName = '';

// Check add_students
$stmt = $conn->prepare("SELECT id, firstname, lastname FROM add_students WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $recipientName = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
    $found = true;
}
$stmt->close();

// Check add_faculties
if (!$found) {
    $stmt = $conn->prepare("SELECT id, firstname, lastname FROM add_faculties WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $recipientName = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
        $found = true;
    }
    $stmt->close();
}

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Email is not registered as a student or faculty account.']);
    $conn->close();
    exit;
}

// Generate 6-digit code
$code    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS password_reset_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
)");

// Keep all fresh unused codes valid for 10 minutes; remove only expired/used ones.
$stmt = $conn->prepare("DELETE FROM password_reset_codes WHERE email = ? AND (expires_at <= NOW() OR used = 1)");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->close();

// Insert new code
$stmt = $conn->prepare("INSERT INTO password_reset_codes (email, code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
$stmt->bind_param("ss", $email, $code);
if (!$stmt->execute()) {
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Failed to generate reset code. Please try again.']);
    exit;
}
$stmt->close();

// Send email
$subject = "Faculty Evaluation System - Password Reset Code";
$body = "
    <h2>Faculty Evaluation System</h2>
    <p>Hello " . htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') . "</p>
    <p>You requested to reset your password.</p>
    <p><b>Your 6-digit verification code:</b></p>
    <h1 style='letter-spacing: 6px; color: #1e3a8a;'>" . $code . "</h1>
    <p>This code expires in 10 minutes.</p>
    <hr>
    <p>If you did not request a password reset, you can ignore this email.</p>
";

$sent = sendEmail($email, $recipientName, $subject, $body);

if ($sent) {
    $conn->close();
    echo json_encode(['success' => true, 'message' => 'A 6-digit reset code has been sent to your email.']);
} else {
    $stmt = $conn->prepare("DELETE FROM password_reset_codes WHERE email = ? AND code = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $email, $code);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();

    $mailError = function_exists('getLastEmailError') ? getLastEmailError() : '';
    $message = 'Failed to send email. Please check Gmail SMTP/app password settings.';
    if ($mailError) {
        $message .= ' Mail error: ' . $mailError;
    }
    echo json_encode(['success' => false, 'message' => $message]);
}
?>

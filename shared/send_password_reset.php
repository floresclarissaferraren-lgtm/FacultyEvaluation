<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/rate_limit.php";
requireMethod("POST");
enforceRateLimit("password_reset_request_ip", 30, 3600);
$email = trim($_POST["email"] ?? "");
enforceRateLimit(
    "password_reset_request_email_" . hash("sha256", strtolower($email)),
    3,
    3600,
);
header("Content-Type: application/json; charset=utf-8");
include __DIR__ . "/connect.php";
include __DIR__ . "/mailer.php";

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid email address.",
    ]);
    exit();
}

// Look up the user — students and faculty only (admin has no email column)
$found = false;
$recipientName = "";

// Check add_students
$stmt = $conn->prepare(
    "SELECT id, firstname, lastname FROM add_students WHERE email = ? LIMIT 1",
);
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $recipientName = trim(
        ($row["firstname"] ?? "") . " " . ($row["lastname"] ?? ""),
    );
    $found = true;
}
$stmt->close();

// Check add_faculties
if (!$found) {
    $stmt = $conn->prepare(
        "SELECT id, firstname, lastname FROM add_faculties WHERE email = ? LIMIT 1",
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $recipientName = trim(
            ($row["firstname"] ?? "") . " " . ($row["lastname"] ?? ""),
        );
        $found = true;
    }
    $stmt->close();
}

if (!$found) {
    echo json_encode([
        "success" => true,
        "message" =>
            "If the email is registered, a reset code will be sent shortly.",
    ]);
    $conn->close();
    exit();
}

// Generate 6-digit code
$code = str_pad(random_int(0, 999999), 6, "0", STR_PAD_LEFT);

// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS password_reset_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    code VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
 ) ENGINE=InnoDB");

$columnResult = $conn->query(
    "SHOW COLUMNS FROM password_reset_codes LIKE 'code'",
);
$codeColumn = $columnResult ? $columnResult->fetch_assoc() : null;
if (!$codeColumn) {
    error_log("Password reset code column is missing.");
    http_response_code(500);
    echo json_encode([
        "success" => true,
        "message" =>
            "If the email is registered, a reset code will be sent shortly.",
    ]);
    $conn->close();
    exit();
}
if (strtolower((string) $codeColumn["Type"]) !== "varchar(255)") {
    $conn->query(
        "ALTER TABLE password_reset_codes MODIFY code VARCHAR(255) NOT NULL",
    );
}

$hashedCode = password_hash($code, PASSWORD_DEFAULT);
if ($hashedCode === false) {
    error_log("Unable to hash password reset code.");
    http_response_code(500);
    echo json_encode([
        "success" => true,
        "message" =>
            "If the email is registered, a reset code will be sent shortly.",
    ]);
    $conn->close();
    exit();
}

$conn->begin_transaction();
$stmt = $conn->prepare(
    "SELECT id FROM password_reset_codes WHERE email = ? FOR UPDATE",
);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->close();

// Keep only the newest active code and store only its password hash.
$stmt = $conn->prepare("DELETE FROM password_reset_codes WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare(
    "INSERT INTO password_reset_codes (email, code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))",
);
$stmt->bind_param("ss", $email, $hashedCode);
if (!$stmt->execute()) {
    $conn->rollback();
    $stmt->close();
    $conn->close();
    echo json_encode([
        "success" => false,
        "message" => "Failed to generate reset code. Please try again.",
    ]);
    exit();
}
$resetId = $stmt->insert_id;
$stmt->close();
$conn->commit();

// Send email
$subject = "Faculty Evaluation System - Password Reset Code";
$protocol =
    !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off" ? "https" : "http";
$host = $_SERVER["HTTP_HOST"] ?? "localhost";
$baseUrl = $protocol . "://" . $host . "/FacultyEvaluation";

$title = "Password Reset Code";
$greeting =
    "Hello " . htmlspecialchars($recipientName, ENT_QUOTES, "UTF-8") . ",";
$content_html = "
    <p style='margin: 0 0 16px 0;'>We received a request to reset the password associated with your account on the Faculty Evaluation System.</p>
    <p style='margin: 0 0 16px 0;'>Please use the 6-digit verification code below to complete your password reset request.</p>
    <p style='margin: 0 0 16px 0;'>If you did not make this request, you can safely ignore this email; your password will remain unchanged.</p>
";

$highlight_box = [
    "label" => "Verification Code",
    "value" => $code,
    "subtext" => "This code expires in 10 minutes",
];

$cta = [
    "label" => "Go to Login Page",
    "url" => $baseUrl . "/EvalMain.php",
];

$body = getEmailHTML($title, $greeting, $content_html, $highlight_box, $cta);

$sent = sendEmail($email, $recipientName, $subject, $body);

if ($sent) {
    $conn->close();
    echo json_encode([
        "success" => true,
        "message" =>
            "If the email is registered, a reset code will be sent shortly.",
    ]);
} else {
    $stmt = $conn->prepare("DELETE FROM password_reset_codes WHERE id = ?");
    $stmt->bind_param("i", $resetId);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    error_log("Faculty evaluation password-reset email could not be sent.");
    echo json_encode([
        "success" => true,
        "message" =>
            "If the email is registered, a reset code will be sent shortly.",
    ]);
}
?>

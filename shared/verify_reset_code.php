<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/rate_limit.php";
requireMethod("POST");
header("Content-Type: application/json; charset=utf-8");

$data = json_decode(file_get_contents("php://input"), true);
$data = is_array($data) ? $data : [];
$emailValue = $data["email"] ?? "";
$codeValue = $data["code"] ?? "";
$passwordValue = $data["password"] ?? "";
$email = is_string($emailValue) ? trim($emailValue) : "";
$code = is_string($codeValue) ? trim($codeValue) : "";
$password = is_string($passwordValue) ? $passwordValue : "";
$check_only = !empty($data["check_only"]); // step 2: just validate, don't consume
enforceRateLimit("password_reset_verify_ip", 60, 900);
enforceRateLimit(
    "password_reset_verify_email_" . hash("sha256", strtolower($email)),
    5,
    900,
);
include __DIR__ . "/connect.php";

if (!$email || !$code) {
    echo json_encode([
        "success" => false,
        "message" => "All fields are required.",
    ]);
    exit();
}

if (strlen($code) !== 6 || !ctype_digit($code)) {
    echo json_encode(["success" => false, "message" => "Invalid code format."]);
    exit();
}

if (!$check_only) {
    if (!$password) {
        echo json_encode([
            "success" => false,
            "message" => "Password is required.",
        ]);
        exit();
    }
    if (strlen($password) < 8) {
        echo json_encode([
            "success" => false,
            "message" => "Password must be at least 8 characters.",
        ]);
        exit();
    }
    if (!preg_match("/[A-Z]/", $password)) {
        echo json_encode([
            "success" => false,
            "message" => "Password must have at least one uppercase letter.",
        ]);
        exit();
    }
    if (!preg_match("/[a-z]/", $password)) {
        echo json_encode([
            "success" => false,
            "message" => "Password must have at least one lowercase letter.",
        ]);
        exit();
    }
    if (!preg_match("/[^A-Za-z0-9]/", $password)) {
        echo json_encode([
            "success" => false,
            "message" => "Password must have at least one special character.",
        ]);
        exit();
    }
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "code_valid" => false,
        "message" => "Invalid or expired code. Please request a new one.",
    ]);
    exit();
}

$conn->begin_transaction();
$stmt = $conn->prepare(
    "SELECT id, code FROM password_reset_codes WHERE email = ? AND expires_at > NOW() AND used = 0 ORDER BY id DESC FOR UPDATE",
);
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

// Legacy plaintext reset codes are deliberately invalidated; newly issued codes are hashed.
$resetId = null;
while ($reset = $res->fetch_assoc()) {
    if (password_verify($code, (string) $reset["code"])) {
        $resetId = (int) $reset["id"];
        break;
    }
}
$stmt->close();

if ($resetId === null) {
    $conn->rollback();
    $conn->close();
    echo json_encode([
        "success" => false,
        "code_valid" => false,
        "message" => "Invalid or expired code. Please request a new one.",
    ]);
    exit();
}

if ($check_only) {
    $conn->commit();
    $conn->close();
    echo json_encode(["success" => true, "code_valid" => true]);
    exit();
}

$stmt = $conn->prepare(
    "UPDATE password_reset_codes SET used = 1 WHERE id = ? AND used = 0 AND expires_at > NOW()",
);
$stmt->bind_param("i", $resetId);
$stmt->execute();
$codeConsumed = $stmt->affected_rows === 1;
$stmt->close();

if (!$codeConsumed) {
    $conn->rollback();
    $conn->close();
    echo json_encode([
        "success" => false,
        "code_valid" => false,
        "message" => "Invalid or expired code. Please request a new one.",
    ]);
    exit();
}

$hashed = password_hash($password, PASSWORD_DEFAULT);
if ($hashed === false) {
    $conn->rollback();
    $conn->close();
    error_log("Unable to hash a password during password reset.");
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Unable to reset password. Please try again.",
    ]);
    exit();
}

// Resolve one account and update it in the same transaction as consuming the code.
$stmt = $conn->prepare(
    "SELECT id FROM add_students WHERE email = ? LIMIT 1 FOR UPDATE",
);
$stmt->bind_param("s", $email);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($account) {
    $accountId = (int) $account["id"];
    $stmt = $conn->prepare("UPDATE add_students SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed, $accountId);
    $stmt->execute();
    $stmt->close();
} else {
    $stmt = $conn->prepare(
        "SELECT id FROM add_faculties WHERE email = ? LIMIT 1 FOR UPDATE",
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$account) {
        $conn->rollback();
        $conn->close();
        echo json_encode([
            "success" => false,
            "message" => "Invalid or expired code. Please request a new one.",
        ]);
        exit();
    }

    $accountId = (int) $account["id"];
    $stmt = $conn->prepare(
        "UPDATE faculty_login SET faculty_password = ? WHERE faculty_id = ?",
    );
    $stmt->bind_param("si", $hashed, $accountId);
    $stmt->execute();
    $updated = $stmt->affected_rows === 1;
    $stmt->close();
    if (!$updated) {
        $conn->rollback();
        $conn->close();
        echo json_encode([
            "success" => false,
            "message" => "Invalid or expired code. Please request a new one.",
        ]);
        exit();
    }
}

$conn->commit();
$conn->close();

echo json_encode([
    "success" => true,
    "message" => "Password reset successfully! You can now log in.",
]);
?>

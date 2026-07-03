<?php
header('Content-Type: application/json');
include 'connect.php';

$data       = json_decode(file_get_contents('php://input'), true);
$email      = trim($data['email']      ?? '');
$code       = trim($data['code']       ?? '');
$password   = $data['password']   ?? '';
$check_only = !empty($data['check_only']); // step 2: just validate, don't consume

if (!$email || !$code) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if (strlen($code) !== 6 || !ctype_digit($code)) {
    echo json_encode(['success' => false, 'message' => 'Invalid code format.']);
    exit;
}

if (!$check_only) {
    if (!$password) {
        echo json_encode(['success' => false, 'message' => 'Password is required.']);
        exit;
    }
    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
        exit;
    }
    if (!preg_match('/[A-Z]/', $password)) {
        echo json_encode(['success' => false, 'message' => 'Password must have at least one uppercase letter.']);
        exit;
    }
    if (!preg_match('/[a-z]/', $password)) {
        echo json_encode(['success' => false, 'message' => 'Password must have at least one lowercase letter.']);
        exit;
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        echo json_encode(['success' => false, 'message' => 'Password must have at least one special character.']);
        exit;
    }
}

// Verify any unused code generated for this email in the last 10 minutes.
$stmt = $conn->prepare("SELECT id FROM password_reset_codes WHERE email = ? AND code = ? AND expires_at > NOW() AND used = 0 ORDER BY id DESC LIMIT 1");
$stmt->bind_param("ss", $email, $code);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => false, 'code_valid' => false, 'message' => 'Invalid or expired code. Please request a new one.']);
    exit;
}

$resetId = $res->fetch_assoc()['id'];
$stmt->close();

// check_only mode: confirm valid without consuming
if ($check_only) {
    $conn->close();
    echo json_encode(['success' => true, 'code_valid' => true]);
    exit;
}

// Mark code as used
$stmt = $conn->prepare("UPDATE password_reset_codes SET used = 1 WHERE id = ?");
$stmt->bind_param("i", $resetId);
$stmt->execute();
$stmt->close();

$hashed  = password_hash($password, PASSWORD_BCRYPT);
$updated = false;

// Update student password in add_students
$stmt = $conn->prepare("UPDATE add_students SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $hashed, $email);
$stmt->execute();
if ($stmt->affected_rows > 0) $updated = true;
$stmt->close();

// Update faculty — password lives in faculty_login, linked by add_faculties.id
if (!$updated) {
    $stmt = $conn->prepare("
        UPDATE faculty_login fl
        INNER JOIN add_faculties af ON af.id = fl.faculty_id
        SET fl.faculty_password = ?
        WHERE af.email = ?
    ");
    $stmt->bind_param("ss", $hashed, $email);
    $stmt->execute();
    if ($stmt->affected_rows > 0) $updated = true;
    $stmt->close();
}

$conn->close();

if ($updated) {
    echo json_encode(['success' => true, 'message' => 'Password reset successfully! You can now log in.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update password. Please try again.']);
}
?>

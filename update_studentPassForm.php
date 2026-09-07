<?php
require_once 'security.php';
requireRole('student');
requireMethod('POST');
header('Content-Type: application/json');
include 'connect.php'; 

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
$student_id   = (string)($_SESSION['id'] ?? '');
$old_password = isset($data['old_password']) ? trim($data['old_password']) : '';
$new_password = isset($data['new_password']) ? trim($data['new_password']) : '';

// Validate required fields
if (empty($student_id) || empty($old_password) || empty($new_password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// Server-side password strength validation
$strength_errors = [];
if (strlen($new_password) < 8)                      $strength_errors[] = "at least 8 characters";
if (!preg_match('/[A-Z]/', $new_password))           $strength_errors[] = "at least one uppercase letter";
if (!preg_match('/[a-z]/', $new_password))           $strength_errors[] = "at least one lowercase letter";
if (!preg_match('/[^A-Za-z0-9]/', $new_password))   $strength_errors[] = "at least one special character";
if (!empty($strength_errors)) {
    echo json_encode(['success' => false, 'message' => 'Password must have: ' . implode(', ', $strength_errors) . '.']);
    exit;
}

$stmt = $conn->prepare("SELECT id, password FROM add_students WHERE id=?");
$stmt->bind_param("i", $student_id); 

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Student not found.']);
    exit;
}

$row = $result->fetch_assoc();
if (!password_verify($old_password, $row['password'])) {
    echo json_encode(['success' => false, 'message' => 'Old password is incorrect.']);
    exit;
}

$hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

// Update in DB
$update = $conn->prepare("UPDATE add_students SET password = ? WHERE id = ?");
$update->bind_param("si", $hashedPassword, $student_id);

if ($update->execute()) {
    echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
}
?>

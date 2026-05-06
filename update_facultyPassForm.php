<?php
session_start();
header('Content-Type: application/json');
include 'connect.php'; 

// Verify user is logged in as faculty
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'faculty') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
$faculty_id   = isset($data['faculty_id'])   ? trim($data['faculty_id'])   : '';
$old_password = isset($data['old_password']) ? trim($data['old_password']) : '';
$new_password = isset($data['new_password']) ? trim($data['new_password']) : '';

// Validate required fields
if (empty($faculty_id) || empty($old_password) || empty($new_password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// Verify that the faculty_id matches the logged-in user's ID
if ($faculty_id != $_SESSION['id']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$stmt = $conn->prepare("SELECT faculty_id, faculty_password FROM faculty_login WHERE faculty_id=?");
$stmt->bind_param("i", $faculty_id); 

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Faculty not found.']);
    exit;
}

$row = $result->fetch_assoc();
if (!password_verify($old_password, $row['faculty_password'])) {
    echo json_encode(['success' => false, 'message' => 'Old password is incorrect.']);
    exit;
}

$hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

// Update in DB
$update = $conn->prepare("UPDATE faculty_login SET faculty_password = ? WHERE faculty_id = ?");
$update->bind_param("si", $hashedPassword, $faculty_id);

if ($update->execute()) {
    echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
}
?>

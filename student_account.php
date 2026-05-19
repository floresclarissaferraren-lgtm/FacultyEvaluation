<?php
include "connect.php";

$student_number = $_POST['student_number'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($student_number) || empty($password)) {
    echo "Please fill all fields";
    exit;
}
$stmt = $conn->prepare("SELECT id, password FROM add_students WHERE student_number=?");
$stmt->bind_param("s", $student_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();

    // Handle NULL password - generate temporary password
    if ($row['password'] === null) {
        $tempPassword = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 8);
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        // Update the student's password in database
        $updateStmt = $conn->prepare("UPDATE add_students SET password = ? WHERE id = ?");
        $updateStmt->bind_param("si", $hashedPassword, $row['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        echo "Your account has been activated. Your temporary password is: $tempPassword. Please use this to login.";
    } elseif (password_verify($password, $row['password'])) {
        $log = $conn->prepare("INSERT INTO student_login (username, password) VALUES (?, ?)");
        $log->bind_param("ss", $student_number, $row['password']);
        $log->execute();

        echo "success";
    } else {
        echo "Invalid password";
    }
} else {
    echo "Student not found";
}
?>

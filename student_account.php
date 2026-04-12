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

    if (password_verify($password, $row['password'])) {
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

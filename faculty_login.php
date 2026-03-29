<?php
session_start();
include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM faculty_login WHERE faculty_username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['faculty_password'])) {
            $_SESSION['role'] = 'faculty';
            $_SESSION['id'] = $user['faculty_id'];
            $_SESSION['username'] = $user['faculty_username'];
            header("Location: faculty_dashboard.php");
            exit();
        } else {
            echo "Incorrect password";
        }
    } else {
        echo "Username not found";
    }
}
?>
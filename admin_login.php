<?php
include_once 'session_config.php';
session_start();
include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admin_login WHERE admin_username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['admin_password'])) {
            
            $_SESSION['role'] = 'admin';
            $_SESSION['id'] = $user['admin_id'];
            $_SESSION['username'] = $user['admin_username'];
            echo 'success';
            exit();
        } else {
            echo "Incorrect password";
        }
    } else {
        echo "Username not found";
    }
    $stmt->close();
}

$conn->close();
?>

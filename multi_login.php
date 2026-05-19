<?php
session_start();
include 'connect.php';

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

header('Content-Type: application/json');

if (empty($username) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Please fill all fields."]);
    exit;
}

// Student login by student number
if (str_starts_with($username, 'GC-')) {
    $stmt = $conn->prepare("SELECT id, password FROM add_students WHERE student_number = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
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
            
            // Return the temporary password to the user
            echo json_encode([
                "success" => false, 
                "message" => "Your account has been activated. Your temporary password is: $tempPassword. Please use this to login.",
                "temp_password" => $tempPassword
            ]);
            $stmt->close();
            $conn->close();
            exit;
        }
        
        if (password_verify($password, $row['password'])) {
            $log = $conn->prepare("INSERT INTO student_login (username, password) VALUES (?, ?)");
            $log->bind_param("ss", $username, $row['password']);
            $log->execute();
            $log->close();

            $_SESSION['role'] = 'student';
            $_SESSION['id'] = $row['id'];
            $_SESSION['username'] = $username;
            echo json_encode(["success" => true, "role" => "student"]);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid password"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Student not found"]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// Faculty login

$stmt = $conn->prepare("SELECT faculty_id, faculty_username, faculty_password FROM faculty_login WHERE faculty_username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['faculty_password'])) {
        $_SESSION['role'] = 'faculty';
        $_SESSION['id'] = $user['faculty_id'];
        $_SESSION['username'] = $user['faculty_username'];
        echo json_encode(["success" => true, "role" => "faculty"]);
    } else {
        echo json_encode(["success" => false, "message" => "Incorrect password"]);
    }
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Admin login fallback
$stmt = $conn->prepare("SELECT admin_id, admin_username, admin_password FROM admin_login WHERE admin_username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['admin_password'])) {
        $_SESSION['role'] = 'admin';
        $_SESSION['id'] = $user['admin_id'];
        $_SESSION['username'] = $user['admin_username'];
        echo json_encode(["success" => true, "role" => "admin"]);
    } else {
        echo json_encode(["success" => false, "message" => "Incorrect password"]);
    }
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

echo json_encode(["success" => false, "message" => "Username not found"]);
$conn->close();
?>
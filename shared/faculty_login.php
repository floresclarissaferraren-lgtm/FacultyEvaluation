<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/rate_limit.php";
requireMethod("POST");
$username = trim($_POST["username"] ?? "");
$password = (string) ($_POST["password"] ?? "");
enforceRateLimit("login_ip", 300, 900);
enforceRateLimit(
    "login_account_" . hash("sha256", strtolower($username)),
    10,
    900,
);
include __DIR__ . "/connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $conn->prepare(
        "SELECT * FROM faculty_login WHERE faculty_username=?",
    );
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user["faculty_password"])) {
            rotateSessionSecurityState();
            $_SESSION["role"] = "faculty";
            $_SESSION["id"] = $user["faculty_id"];
            $_SESSION["username"] = $user["faculty_username"];
            header("Location: ../instructor/FacultyInstructor.php");
            exit();
        } else {
            echo "Invalid username or password.";
        }
    } else {
        echo "Invalid username or password.";
    }
}
?>

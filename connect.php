<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "faculty_evaluation_db"; // <-- tama dapat ito

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>

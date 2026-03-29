<?php
include "connect.php";

// ✅ CHECK if POST request exists
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  echo "invalid";
  exit;
}

// ✅ SAFE access (no more warnings)
$faculty_id = $_POST['faculty_id'] ?? '';
$email      = $_POST['email'] ?? '';
$firstname  = $_POST['firstname'] ?? '';
$lastname   = $_POST['lastname'] ?? '';
$suffix     = $_POST['suffix'] ?? '';
$photo      = $_POST['photo'] ?? '';

// ✅ VALIDATION
if (!$faculty_id || !$email || !$firstname || !$lastname) {
  echo "missing";
  exit;
}

// ✅ CHECK DUPLICATE
$check = $conn->prepare("SELECT faculty_id FROM add_faculties WHERE faculty_id=?");
$check->bind_param("s", $faculty_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
  echo "duplicate";
  exit;
}

// ✅ INSERT
$stmt = $conn->prepare("INSERT INTO add_faculties (faculty_id,email,firstname,lastname,suffix,photo) VALUES (?,?,?,?,?,?)");
$stmt->bind_param("ssssss", $faculty_id, $email, $firstname, $lastname, $suffix, $photo);

if ($stmt->execute()) {
  echo "success";
} else {
  echo "error";
}
?>
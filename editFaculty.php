<?php
include "connect.php";

$faculty_id = $_POST['faculty_id'] ?? '';
$email      = $_POST['email'] ?? '';
$firstname  = $_POST['firstname'] ?? '';
$lastname   = $_POST['lastname'] ?? '';
$suffix     = $_POST['suffix'] ?? '';
$photo      = $_POST['photo'] ?? '';

$stmt = $conn->prepare("UPDATE add_faculties SET email=?, firstname=?, lastname=?, suffix=?, photo=? WHERE faculty_id=?");
if(!$stmt){ die("Prepare failed: ".$conn->error); }

$stmt->bind_param("ssssss", $email, $firstname, $lastname, $suffix, $photo, $faculty_id);

if($stmt->execute()){
  echo "success";
} else {
  echo "error: ".$stmt->error;
}
?>

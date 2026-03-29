<?php
include "connect.php";

$faculty_id = $_POST['faculty_id'];

$stmt = $conn->prepare("DELETE FROM add_faculties WHERE faculty_id=?");
$stmt->bind_param("s", $faculty_id);

if ($stmt->execute()) {
  echo "success";
} else {
  echo "error: " . $stmt->error;
}
?>

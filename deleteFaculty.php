<?php
include "connect.php";

$faculty_id = $_POST['faculty_id'] ?? null;

if (!$faculty_id) {
  echo json_encode(["success" => false, "error" => "Missing faculty_id"]);
  exit;
}

$stmt = $conn->prepare("DELETE FROM add_faculties WHERE faculty_id=?");
$stmt->bind_param("s", $faculty_id);

if ($stmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false, "error" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>

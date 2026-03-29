<?php
include 'connect.php';

// 🔥 IMPORTANT: set JSON header
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  $code = $_POST['program_code'] ?? '';
  $name = $_POST['program_name'] ?? '';

  // basic validation
  if (empty($code) || empty($name)) {
    echo json_encode([
      "status" => "error",
      "message" => "Program code and name are required."
    ]);
    exit;
  }

  // check if program already exists
  $check = $conn->prepare("SELECT id FROM add_programs WHERE program_code = ?");
  $check->bind_param("s", $code);
  $check->execute();
  $check->store_result();

  if ($check->num_rows > 0) {
    echo json_encode([
      "status" => "error",
      "message" => "Program code already exists."
    ]);
    $check->close();
    exit;
  }

  $check->close();

  $stmt = $conn->prepare("INSERT INTO add_programs (program_code, program_name) VALUES (?, ?)");
  $stmt->bind_param("ss", $code, $name);

  if ($stmt->execute()) {

    echo json_encode([
      "status" => "success",
      "id" => $conn->insert_id,
      "program_code" => $code,
      "program_name" => $name
    ]);

  } else {
    echo json_encode([
      "status" => "error",
      "message" => $stmt->error
    ]);
  }

  $stmt->close();
  $conn->close();
}
?>
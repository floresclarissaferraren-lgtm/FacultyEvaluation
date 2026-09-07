<?php
require_once 'security.php';
requireRole('admin');
requireMethod('POST');
include 'connect.php';

// 🔥 IMPORTANT: set JSON header
header('Content-Type: application/json');

function normalizeProgramCodeInput(string $code): string {
  $code = trim($code);
  $code = preg_replace('/\\\\[rnt]/i', '', $code);
  $code = preg_replace('/\s+/', '', $code);
  return strtoupper($code);
}

function programCodeExists(mysqli $conn, string $normalizedCode): bool {
  $result = $conn->query("SELECT program_code FROM add_programs");
  if (!$result) return false;

  while ($row = $result->fetch_assoc()) {
    if (normalizeProgramCodeInput((string)$row['program_code']) === $normalizedCode) {
      return true;
    }
  }

  return false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  $code = normalizeProgramCodeInput($_POST['program_code'] ?? '');
  $name = trim($_POST['program_name'] ?? '');

  // basic validation
  if (empty($code) || empty($name)) {
    echo json_encode([
      "status" => "error",
      "message" => "Program code and name are required."
    ]);
    exit;
  }

  if (programCodeExists($conn, $code)) {
    echo json_encode([
      "status" => "error",
      "message" => "Program code already exists. Please use a different program code."
    ]);
    exit;
  }
  
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

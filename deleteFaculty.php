<?php
require_once 'security.php';
requireRole('admin');
requireMethod('POST');
include "connect.php";

$faculty_id = $_POST['faculty_id'] ?? null;

if (!$faculty_id) {
  echo json_encode(["success" => false, "error" => "Missing faculty_id"]);
  exit;
}

// Start transaction for safe deletion
$conn->begin_transaction();

try {
  // Get the faculty ID from add_faculties table first
  $getFacultyStmt = $conn->prepare("SELECT id FROM add_faculties WHERE faculty_id = ?");
  $getFacultyStmt->bind_param("s", $faculty_id);
  $getFacultyStmt->execute();
  $result = $getFacultyStmt->get_result();
  
  if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "error" => "Faculty not found"]);
    exit;
  }
  
  $facultyRow = $result->fetch_assoc();
  $facultyDbId = $facultyRow['id'];
  $getFacultyStmt->close();
  
  // Delete from faculty_login table
  $deleteLoginStmt = $conn->prepare("DELETE FROM faculty_login WHERE faculty_id = ?");
  $deleteLoginStmt->bind_param("i", $facultyDbId);
  $deleteLoginStmt->execute();
  $deleteLoginStmt->close();
  
  // Delete from class_subjects table (remove faculty assignments)
  $deleteClassSubjectsStmt = $conn->prepare("DELETE FROM class_subjects WHERE faculty_id = ?");
  $deleteClassSubjectsStmt->bind_param("i", $facultyDbId);
  $deleteClassSubjectsStmt->execute();
  $deleteClassSubjectsStmt->close();
  
  // Delete any evaluations related to this faculty
  $deleteEvaluationsStmt = $conn->prepare("DELETE FROM evaluations WHERE faculty_id = ?");
  $deleteEvaluationsStmt->bind_param("i", $facultyDbId);
  $deleteEvaluationsStmt->execute();
  $deleteEvaluationsStmt->close();
  
  // Finally delete from add_faculties table
  $deleteFacultyStmt = $conn->prepare("DELETE FROM add_faculties WHERE faculty_id = ?");
  $deleteFacultyStmt->bind_param("s", $faculty_id);
  $deleteFacultyStmt->execute();
  $deleteFacultyStmt->close();
  
  // Commit transaction
  $conn->commit();
  echo json_encode(["success" => true]);
  
} catch (Exception $e) {
  // Rollback on error
  $conn->rollback();
  echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}

$conn->close();
?>

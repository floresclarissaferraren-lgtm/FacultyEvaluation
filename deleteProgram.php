<?php
require_once 'security.php';
requireRole('admin');
requireMethod('POST');
include "connect.php";
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $program_id = isset($_POST['id']) && is_numeric($_POST['id']) ? intval($_POST['id']) : 0;
    $program_code = trim($_POST['program_code'] ?? '');

    if ($program_id <= 0 && $program_code === '') {
        echo json_encode(['success' => false, 'error' => 'Missing program id or program_code']);
        exit;
    }

    // Start transaction for safe deletion
    $conn->begin_transaction();

    try {
        // Get program details first
        if ($program_id > 0) {
            $program_stmt = $conn->prepare("SELECT id, program_code FROM add_programs WHERE id = ? LIMIT 1");
            $program_stmt->bind_param("i", $program_id);
        } else {
            $program_stmt = $conn->prepare("SELECT id, program_code FROM add_programs WHERE TRIM(program_code) = TRIM(?) LIMIT 1");
            $program_stmt->bind_param("s", $program_code);
        }
        $program_stmt->execute();
        $program_result = $program_stmt->get_result();
        
        if ($program_result->num_rows === 0) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => 'Program not found']);
            exit;
        }
        
        $program = $program_result->fetch_assoc();
        $program_id = intval($program['id']);
        $program_code = $program['program_code'];
        $program_stmt->close();

        // Get all subjects under this program
        $subjects_stmt = $conn->prepare("SELECT id FROM add_subjects WHERE program_id = ?");
        $subjects_stmt->bind_param("i", $program_id);
        $subjects_stmt->execute();
        $subjects_result = $subjects_stmt->get_result();
        $subject_ids = [];
        
        while ($row = $subjects_result->fetch_assoc()) {
            $subject_ids[] = $row['id'];
        }
        $subjects_stmt->close();

        // Delete student_subjects for these subjects
        if (!empty($subject_ids)) {
            $subject_ids_str = implode(',', $subject_ids);
            $conn->query("DELETE FROM student_subjects WHERE subject_id IN ($subject_ids_str)");
            $conn->query("DELETE FROM faculty_subjects WHERE subject_id IN ($subject_ids_str)");
            $conn->query("DELETE FROM class_subjects WHERE subject_id IN ($subject_ids_str)");
        }

        // Delete subjects
        $delete_subjects = $conn->prepare("DELETE FROM add_subjects WHERE program_id = ?");
        $delete_subjects->bind_param("i", $program_id);
        $delete_subjects->execute();
        $delete_subjects->close();

        // Delete classes
        $delete_classes = $conn->prepare("DELETE FROM add_classes WHERE program_id = ?");
        $delete_classes->bind_param("i", $program_id);
        $delete_classes->execute();
        $delete_classes->close();

        // Delete students (program may store program_id, code, or old code text)
        $program_id_text = (string)$program_id;
        $delete_students = $conn->prepare("DELETE FROM add_students WHERE program = ? OR TRIM(program) = TRIM(?)");
        $delete_students->bind_param("ss", $program_id_text, $program_code);
        $delete_students->execute();
        $delete_students->close();

        // Delete the program itself
        $delete_program = $conn->prepare("DELETE FROM add_programs WHERE id = ?");
        $delete_program->bind_param("i", $program_id);
        $delete_program->execute();
        
        if ($delete_program->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'deleted_code' => $program_code]);
        } else {
            throw new Exception("Failed to delete program");
        }
        $delete_program->close();

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

    $conn->close();
}
?>

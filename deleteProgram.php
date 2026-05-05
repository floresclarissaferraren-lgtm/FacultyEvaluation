<?php
include "connect.php";
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $program_code = $_POST['program_code'] ?? '';

    if (empty($program_code)) {
        echo json_encode(['success' => false, 'error' => 'Missing program_code']);
        exit;
    }

    // Start transaction for safe deletion
    $conn->begin_transaction();

    try {
        // Get program ID first
        $program_stmt = $conn->prepare("SELECT id FROM add_programs WHERE program_code = ?");
        $program_stmt->bind_param("s", $program_code);
        $program_stmt->execute();
        $program_result = $program_stmt->get_result();
        
        if ($program_result->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Program not found']);
            exit;
        }
        
        $program_id = $program_result->fetch_assoc()['id'];
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

        // Delete students (using program field that stores program_id)
        $delete_students = $conn->prepare("DELETE FROM add_students WHERE program = ?");
        $delete_students->bind_param("s", $program_id);
        $delete_students->execute();
        $delete_students->close();

        // Delete the program itself
        $delete_program = $conn->prepare("DELETE FROM add_programs WHERE program_code = ?");
        $delete_program->bind_param("s", $program_code);
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

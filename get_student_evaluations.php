<?php
/**
 * Get Student Evaluations
 * Returns the list of evaluations submitted by a student
 */

include_once 'session_config.php';
session_start();
header('Content-Type: application/json');
include 'connect.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

$studentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

// Validate student_id
if ($studentId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid student ID',
        'evaluations' => []
    ]);
    exit;
}

// Verify the student_id matches the session
if (isset($_SESSION['id']) && intval($_SESSION['id']) !== $studentId) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access to student data',
        'evaluations' => []
    ]);
    exit;
}

try {
    // Get all evaluations by this student
    $stmt = $conn->prepare("
        SELECT 
            e.id,
            e.faculty_id,
            e.subject_id,
            e.class_id,
            e.period_id,
            e.created_at,
            f.firstname AS faculty_firstname,
            f.lastname AS faculty_lastname,
            f.suffix AS faculty_suffix,
            s.subject_code,
            s.subject_desc,
            ep.ay AS academic_year,
            ep.semester
        FROM evaluations e
        LEFT JOIN add_faculties f ON f.id = e.faculty_id
        LEFT JOIN add_subjects s ON s.id = e.subject_id
        LEFT JOIN evaluation_periods ep ON ep.id = e.period_id
        WHERE e.student_id = ?
        ORDER BY e.created_at DESC
    ");
    
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $evaluations = [];
    while ($row = $result->fetch_assoc()) {
        $facultyName = trim(
            ($row['faculty_firstname'] ?? '') . ' ' . 
            ($row['faculty_lastname'] ?? '') . 
            ($row['faculty_suffix'] ? ' ' . $row['faculty_suffix'] : '')
        );
        
        $subjectLabel = trim(
            ($row['subject_code'] ?? '') . 
            ($row['subject_desc'] ? ' - ' . $row['subject_desc'] : '')
        );
        
        $periodLabel = trim(
            ($row['academic_year'] ?? '') . 
            ($row['semester'] ? ' - ' . $row['semester'] : '')
        );
        
        $evaluations[] = [
            'id' => intval($row['id']),
            'faculty_id' => intval($row['faculty_id']),
            'faculty_name' => $facultyName,
            'subject_id' => intval($row['subject_id']),
            'subject_label' => $subjectLabel,
            'class_id' => intval($row['class_id']),
            'period_id' => intval($row['period_id']),
            'period_label' => $periodLabel,
            'created_at' => $row['created_at']
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'count' => count($evaluations),
        'evaluations' => $evaluations
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_student_evaluations.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'evaluations' => []
    ]);
}

$conn->close();
?>

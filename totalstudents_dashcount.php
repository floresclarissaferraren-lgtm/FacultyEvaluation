<?php
include 'connect.php';

function getTotalCount($conn, $table) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM {$table}");
    if (!$stmt) {
        return 0;
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        return 0;
    }
    $row = $result->fetch_assoc();
    $stmt->close();
    return isset($row['total']) ? (int) $row['total'] : 0;
}

function getStudentStatistics($conn) {
    $stats = [];
    
    // Total Students
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM add_students");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['total_students'] = isset($row['total']) ? (int) $row['total'] : 0;
    $stmt->close();
    
    // Active Students (assuming active means with enrolled subjects)
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT student_id) as total FROM student_subjects");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['active_students'] = isset($row['total']) ? (int) $row['total'] : 0;
    $stmt->close();
    
    // Regular Students
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM add_students WHERE student_type = 'regular'");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['regular_students'] = isset($row['total']) ? (int) $row['total'] : 0;
    $stmt->close();
    
    // Irregular Students
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM add_students WHERE student_type = 'irregular'");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['irregular_students'] = isset($row['total']) ? (int) $row['total'] : 0;
    $stmt->close();
    
    return $stats;
}

$totalFacultyCount = getTotalCount($conn, 'add_faculties');
$totalStudentsCount = getTotalCount($conn, 'add_students');
$totalEvaluationsCount = 0;
$totalProgramsCount = getTotalCount($conn, 'add_programs');

function getFacultyStatistics($conn) {
    $stats = [];
    
    // Total Faculty
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM add_faculties");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['total_faculty'] = isset($row['total']) ? (int) $row['total'] : 0;
    $stmt->close();
    
    // Active Faculty (assuming active means with assigned subjects)
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT faculty_id) as total FROM faculty_subjects");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['active_faculty'] = isset($row['total']) ? (int) $row['total'] : 0;
    $stmt->close();
    
    // Pending Evaluation (faculty who haven't been evaluated yet)
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT f.id) as total 
        FROM add_faculties f 
        LEFT JOIN evaluations e ON f.id = e.faculty_id 
        WHERE e.faculty_id IS NULL
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['pending_evaluation'] = isset($row['total']) ? (int) $row['total'] : 0;
    $stmt->close();
    
    return $stats;
}

// Get student statistics
$studentStats = getStudentStatistics($conn);

// Get faculty statistics
$facultyStats = getFacultyStatistics($conn); 
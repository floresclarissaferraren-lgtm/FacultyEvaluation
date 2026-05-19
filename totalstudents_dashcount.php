<?php
include 'connect.php';

function ensureStatusColumn($conn, $table) {
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $check = $conn->query("SHOW COLUMNS FROM {$safeTable} LIKE 'status'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE {$safeTable} ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'");
    }
}

ensureStatusColumn($conn, 'add_students');
ensureStatusColumn($conn, 'add_faculties');

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
    
    // Active Students
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM add_students WHERE LOWER(TRIM(status)) = 'active'");
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
    
    // Active Faculty
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM add_faculties WHERE LOWER(TRIM(status)) = 'active'");
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
          AND LOWER(TRIM(COALESCE(f.status, 'active'))) = 'active'
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

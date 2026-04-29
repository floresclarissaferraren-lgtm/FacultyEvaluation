<?php
include "connect.php";

header("Content-Type: application/json");

$action = $_REQUEST['action'] ?? "";

/* ========================= GET FACULTY BY SUBJECT ========================= */
if ($action === "get_faculty_by_subject") {
    $subject_id = $_GET['subject_id'] ?? 0;
    
    if (!$subject_id) {
        echo json_encode([]);
        exit;
    }
    
    $stmt = $conn->prepare("
        SELECT DISTINCT f.id, f.faculty_id, f.firstname, f.lastname, f.suffix
        FROM add_faculties f
        JOIN faculty_subjects fs ON f.id = fs.faculty_id
        WHERE fs.subject_id = ?
        ORDER BY f.lastname ASC, f.firstname ASC
    ");
    
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $faculty = [];
    
    while ($row = $result->fetch_assoc()) {
        $faculty[] = [
            'id' => $row['id'],
            'faculty_id' => $row['faculty_id'],
            'name' => $row['firstname'] . ' ' . $row['lastname'] . ' ' . ($row['suffix'] ?? ''),
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'suffix' => $row['suffix'] ?? ''
        ];
    }
    
    echo json_encode($faculty);
    exit;
}

/* ========================= GET FACULTY BY MULTIPLE SUBJECTS ========================= */
if ($action === "get_faculty_by_subjects") {
    $subject_ids = $_GET['subject_ids'] ?? "";
    
    if (empty($subject_ids)) {
        echo json_encode([]);
        exit;
    }
    
    $subject_array = explode(",", $subject_ids);
    $placeholders = str_repeat('?,', count($subject_array) - 1) . '?';
    
    $stmt = $conn->prepare("
        SELECT DISTINCT f.id, f.faculty_id, f.firstname, f.lastname, f.suffix
        FROM add_faculties f
        JOIN faculty_subjects fs ON f.id = fs.faculty_id
        WHERE fs.subject_id IN ($placeholders)
        ORDER BY f.lastname ASC, f.firstname ASC
    ");
    
    $stmt->bind_param(str_repeat('i', count($subject_array)), ...$subject_array);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $faculty = [];
    
    while ($row = $result->fetch_assoc()) {
        $faculty[] = [
            'id' => $row['id'],
            'faculty_id' => $row['faculty_id'],
            'name' => $row['firstname'] . ' ' . $row['lastname'] . ' ' . ($row['suffix'] ?? ''),
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'suffix' => $row['suffix'] ?? ''
        ];
    }
    
    echo json_encode($faculty);
    exit;
}

/* ========================= GET SUBJECTS BY PROGRAM AND YEAR ========================= */
if ($action === "get_subjects_by_program_year") {
    $program_id = $_GET['program_id'] ?? 0;
    $year_level = $_GET['year_level'] ?? "";
    
    if (!$program_id || !$year_level) {
        echo json_encode([]);
        exit;
    }
    
    $stmt = $conn->prepare("
        SELECT id, subject_code, subject_desc, year_level
        FROM add_subjects
        WHERE program_id = ? AND year_level = ?
        ORDER BY subject_code ASC
    ");
    
    $stmt->bind_param("is", $program_id, $year_level);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $subjects = [];
    
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    
    echo json_encode($subjects);
    exit;
}

/* ========================= GET CLASSES BY PROGRAM ========================= */
if ($action === "get") {
    $program_id = $_GET['program_id'] ?? 0;
    
    if (!$program_id) {
        echo json_encode([]);
        exit;
    }
    
    // Create table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS classes (
        id int(11) AUTO_INCREMENT PRIMARY KEY,
        program_id int(11) NOT NULL,
        section_name varchar(100) NOT NULL,
        year_level varchar(20) NOT NULL,
        block varchar(10) NOT NULL,
        status enum('active','inactive') DEFAULT 'active',
        created_at timestamp DEFAULT CURRENT_TIMESTAMP
    )");
    
    $stmt = $conn->prepare("
        SELECT id, section_name, year_level, block, status
        FROM classes
        WHERE program_id = ?
        ORDER BY year_level ASC, section_name ASC
    ");
    
    if ($stmt) {
        $stmt->bind_param("i", $program_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $classes = [];
        
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
        
        echo json_encode($classes);
    } else {
        echo json_encode([]);
    }
    exit;
}

/* ========================= ADD CLASS ========================= */
if ($action === "add") {
    $program_id = $_POST['program_id'] ?? null;
    $section_name = $_POST['section_name'] ?? "";
    $year_level = $_POST['year_level'] ?? "";
    $block = $_POST['block'] ?? "";
    
    $subjects = [];
    if (isset($_POST['subjects']) && is_array($_POST['subjects'])) {
        $subjects = $_POST['subjects'];
    }
    
    if (!$program_id || !$section_name || !$year_level || !$block) {
        echo json_encode(["status"=>"error","message"=>"Missing required fields"]);
        exit;
    }
    
    // Create tables if not exist
    $conn->query("CREATE TABLE IF NOT EXISTS classes (
        id int(11) AUTO_INCREMENT PRIMARY KEY,
        program_id int(11) NOT NULL,
        section_name varchar(100) NOT NULL,
        year_level varchar(20) NOT NULL,
        block varchar(10) NOT NULL,
        status enum('active','inactive') DEFAULT 'active',
        created_at timestamp DEFAULT CURRENT_TIMESTAMP
    )");
    
    $conn->query("CREATE TABLE IF NOT EXISTS class_subjects (
        id int(11) AUTO_INCREMENT PRIMARY KEY,
        class_id int(11) NOT NULL,
        subject_id int(11) NOT NULL,
        UNIQUE KEY unique_class_subject (class_id, subject_id)
    )");
    
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("INSERT INTO classes (program_id, section_name, year_level, block) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $program_id, $section_name, $year_level, $block);
        $stmt->execute();
        $class_id = $stmt->insert_id;
        $stmt->close();
        
        if (!empty($subjects)) {
            $sub_stmt = $conn->prepare("INSERT INTO class_subjects (class_id, subject_id) VALUES (?, ?)");
            foreach ($subjects as $subject_id) {
                $sub_stmt->bind_param("ii", $class_id, $subject_id);
                $sub_stmt->execute();
            }
            $sub_stmt->close();
        }
        
        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Class added successfully"]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status"=>"error","message"=>"Failed to add class"]);
    }
    
    exit;
}

echo json_encode(["status" => "error", "message" => "Invalid action"]);
?>

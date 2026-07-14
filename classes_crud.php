<?php
include "connect.php";

header("Content-Type: application/json");

$action = $_REQUEST['action'] ?? "";

function ensureClassSemesterColumn(mysqli $conn): void {
    $check = $conn->query("SHOW COLUMNS FROM add_classes LIKE 'semester'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE add_classes ADD COLUMN semester VARCHAR(20) NOT NULL DEFAULT '1st Semester' AFTER year_level");
    }
}

function ensureSubjectSemesterColumn(mysqli $conn): void {
    $check = $conn->query("SHOW COLUMNS FROM add_subjects LIKE 'semester'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE add_subjects ADD COLUMN semester VARCHAR(20) NOT NULL DEFAULT '1st Semester' AFTER subject_desc");
    }
}

function classYearVariants(string $classYear): array {
    $variants = [trim($classYear)];
    if (preg_match('/^(\d+)/', $classYear, $m)) {
        $num = $m[1];
        if (!in_array($num, $variants, true)) {
            $variants[] = $num;
        }
    }
    return array_values(array_filter(array_unique($variants)));
}

function syncRegularStudentsForClass(mysqli $conn, int $class_id): void {
    $classStmt = $conn->prepare("SELECT program_id, year_level, block FROM add_classes WHERE id = ? LIMIT 1");
    if (!$classStmt) {
        return;
    }
    $classStmt->bind_param("i", $class_id);
    $classStmt->execute();
    $classRes = $classStmt->get_result();
    $class = $classRes ? $classRes->fetch_assoc() : null;
    $classStmt->close();

    if (!$class) {
        return;
    }

    $program_id = intval($class['program_id']);
    $year_level = trim((string)$class['year_level']);
    $block = trim((string)$class['block']);
    $yearVariants = classYearVariants($year_level);

    if (empty($yearVariants)) {
        return;
    }

    $yearPlaceholders = implode(',', array_fill(0, count($yearVariants), '?'));
    $studentSql = "
        SELECT s.id
        FROM add_students s
        LEFT JOIN add_programs p ON p.id = ?
        WHERE (
            s.program = CAST(? AS CHAR)
            OR UPPER(TRIM(s.program)) = UPPER(TRIM(p.program_code))
            OR UPPER(TRIM(s.program)) = UPPER(TRIM(p.program_name))
        )
          AND TRIM(UPPER(s.section)) = TRIM(UPPER(?))
          AND LOWER(TRIM(s.yearlevel)) != 'irregular'
          AND s.yearlevel IN ($yearPlaceholders)
    ";

    $studentStmt = $conn->prepare($studentSql);
    if (!$studentStmt) {
        return;
    }

    $types = "iis" . str_repeat("s", count($yearVariants));
    $params = [$program_id, $program_id, $block, ...$yearVariants];
    $refs = [];
    foreach ($params as $k => $v) {
        $refs[$k] = &$params[$k];
    }
    array_unshift($refs, $types);
    call_user_func_array([$studentStmt, "bind_param"], $refs);
    $studentStmt->execute();
    $studentRes = $studentStmt->get_result();

    $studentIds = [];
    while ($row = $studentRes->fetch_assoc()) {
        $studentIds[] = intval($row['id']);
    }
    $studentStmt->close();

    if (empty($studentIds)) {
        return;
    }

    $subjectStmt = $conn->prepare("SELECT subject_id FROM class_subjects WHERE class_id = ? ORDER BY subject_id ASC");
    if (!$subjectStmt) {
        return;
    }
    $subjectStmt->bind_param("i", $class_id);
    $subjectStmt->execute();
    $subjectRes = $subjectStmt->get_result();
    $subjectIds = [];
    while ($row = $subjectRes->fetch_assoc()) {
        $subjectIds[] = intval($row['subject_id']);
    }
    $subjectStmt->close();

    $conn->query("CREATE TABLE IF NOT EXISTS student_subject_classes (
        id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        student_id int(11) NOT NULL,
        subject_id int(11) NOT NULL,
        class_id int(11) NOT NULL,
        UNIQUE KEY unique_student_subject_class (student_id, subject_id),
        KEY idx_ssc_student (student_id),
        KEY idx_ssc_class (class_id)
    )");

    $del = $conn->prepare("DELETE FROM student_subjects WHERE student_id = ?");
    $delClass = $conn->prepare("DELETE FROM student_subject_classes WHERE student_id = ?");
    $ins = $conn->prepare("INSERT INTO student_subjects (student_id, subject_id) VALUES (?, ?)");
    $insClass = $conn->prepare("INSERT INTO student_subject_classes (student_id, subject_id, class_id) VALUES (?, ?, ?)");
    if (!$del || !$delClass || !$ins || !$insClass) {
        if ($del) $del->close();
        if ($delClass) $delClass->close();
        if ($ins) $ins->close();
        if ($insClass) $insClass->close();
        return;
    }

    foreach ($studentIds as $sid) {
        $del->bind_param("i", $sid);
        $del->execute();
        $delClass->bind_param("i", $sid);
        $delClass->execute();

        foreach ($subjectIds as $subjId) {
            $ins->bind_param("ii", $sid, $subjId);
            $ins->execute();
            $insClass->bind_param("iii", $sid, $subjId, $class_id);
            $insClass->execute();
        }
    }

    $del->close();
    $delClass->close();
    $ins->close();
    $insClass->close();
}

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
    $semester = trim($_GET['semester'] ?? "");
    
    if (!$program_id || !$year_level) {
        echo json_encode([]);
        exit;
    }
    
    ensureSubjectSemesterColumn($conn);

    if ($semester !== "") {
        $stmt = $conn->prepare("
            SELECT id, subject_code, subject_desc, semester, year_level
            FROM add_subjects
            WHERE program_id = ?
              AND year_level = ?
              AND LOWER(TRIM(semester)) = LOWER(TRIM(?))
            ORDER BY subject_code ASC
        ");
        $stmt->bind_param("iss", $program_id, $year_level, $semester);
    } else {
        $stmt = $conn->prepare("
            SELECT id, subject_code, subject_desc, semester, year_level
            FROM add_subjects
            WHERE program_id = ? AND year_level = ?
            ORDER BY semester ASC, subject_code ASC
        ");
        $stmt->bind_param("is", $program_id, $year_level);
    }
    $stmt->execute();
    
    $result = $stmt->get_result();
    $subjects = [];
    
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    
    echo json_encode($subjects);
    exit;
}

/* ========================= GET CLASS OPTIONS BY SUBJECT ========================= */
if ($action === "get_subject_class_options") {
    $program_id = intval($_GET['program_id'] ?? 0);
    $subject_id = intval($_GET['subject_id'] ?? 0);

    if (!$program_id || !$subject_id) {
        echo json_encode([]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT
            ac.id AS class_id,
            ac.year_level,
            ac.block AS section,
            cs.subject_id,
            cs.faculty_id,
            TRIM(CONCAT(f.firstname, ' ', f.lastname, ' ', COALESCE(f.suffix, ''))) AS instructor_name
        FROM add_classes ac
        INNER JOIN class_subjects cs ON cs.class_id = ac.id AND cs.subject_id = ?
        LEFT JOIN add_faculties f ON f.id = cs.faculty_id
        WHERE ac.program_id = ?
        ORDER BY ac.year_level ASC, ac.block ASC
    ");
    $stmt->bind_param("ii", $subject_id, $program_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    echo json_encode($rows);
    exit;
}

/* ========================= GET CLASSES OR CLASS SUBJECTS ========================= */
if ($action === "get") {
    $class_id = $_GET['class_id'] ?? 0;
    if ($class_id) {
        error_log("GET CLASS SUBJECTS - class_id: $class_id");
        
        ensureSubjectSemesterColumn($conn);

        $stmt = $conn->prepare("
            SELECT cs.subject_id, s.subject_code, s.subject_desc, s.semester, s.year_level,
                f.id AS faculty_id, f.faculty_id AS faculty_number,
                CONCAT(f.firstname, ' ', f.lastname, ' ', COALESCE(f.suffix, '')) AS faculty_name
            FROM class_subjects cs
            JOIN add_subjects s ON cs.subject_id = s.id
            LEFT JOIN add_faculties f ON cs.faculty_id = f.id
            WHERE cs.class_id = ?
            ORDER BY s.subject_code ASC
        ");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $subjects = [];
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
        
        error_log("GET CLASS SUBJECTS - Found " . count($subjects) . " subjects: " . print_r($subjects, true));
        echo json_encode($subjects);
        exit;
    }

    $program_id = $_GET['program_id'] ?? 0;
    
    if (!$program_id) {
        echo json_encode([]);
        exit;
    }
    
    // Create table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS add_classes (
        id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        program_id int(11) NOT NULL,
        year_level varchar(20) NOT NULL,
        semester varchar(20) NOT NULL DEFAULT '1st Semester',
        block varchar(10) NOT NULL
    )");
    ensureClassSemesterColumn($conn);
    
    $stmt = $conn->prepare("
        SELECT id, year_level, block
        FROM add_classes
        WHERE program_id = ?
        ORDER BY year_level ASC, block ASC
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
    error_log("ADD CLASS - Raw POST data: " . print_r($_POST, true));
    
    $program_id = $_POST['program_id'] ?? null;
    $section_name = $_POST['section_name'] ?? "";
    $year_level = $_POST['year_level'] ?? "";
    $block = $_POST['block'] ?? "";
    
    $subjects = [];
    if (isset($_POST['subjects'])) {
        $subjects = json_decode($_POST['subjects'], true);
        if (!is_array($subjects)) {
            $subjects = [];
        }
    }

    $faculty_assignments = [];
    if (isset($_POST['faculty_assignments'])) {
        $faculty_assignments = json_decode($_POST['faculty_assignments'], true);
        if (!is_array($faculty_assignments)) {
            $faculty_assignments = [];
        }
    }
    
    error_log("ADD CLASS - Parsed data: program_id=$program_id, section_name=$section_name, year_level=$year_level, block=$block");
    error_log("ADD CLASS - Subjects: " . print_r($subjects, true));
    error_log("ADD CLASS - Faculty assignments: " . print_r($faculty_assignments, true));
    
    if (!$program_id || !$year_level || !$block || empty($subjects)) {
        echo json_encode(["status"=>"error","message"=>"Missing required fields"]);
        exit;
    }
    
    // Check for duplicate class (same year level and section in same program)
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM add_classes WHERE program_id = ? AND year_level = ? AND block = ?");
    $check_stmt->bind_param("iss", $program_id, $year_level, $block);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    $check_stmt->close();
    
    if ($row['count'] > 0) {
        echo json_encode([
            "status" => "error", 
            "message" => "The faculty or subject already exist in this section"
        ]);
        exit;
    }
    
    // Create tables if not exist
    $conn->query("CREATE TABLE IF NOT EXISTS add_classes (
        id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        program_id int(11) NOT NULL,
        year_level varchar(20) NOT NULL,
        semester varchar(20) NOT NULL DEFAULT '1st Semester',
        block varchar(10) NOT NULL
    )");
    ensureClassSemesterColumn($conn);
    
    $conn->query("CREATE TABLE IF NOT EXISTS class_subjects (
        id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        class_id int(11) NOT NULL,
        subject_id int(11) NOT NULL,
        faculty_id int(11) NOT NULL,
        UNIQUE KEY unique_class_subject (class_id, subject_id)
    )");
    
    $conn->begin_transaction();
    
    try {
        error_log("ADD CLASS - Starting transaction");
        
        $stmt = $conn->prepare("INSERT INTO add_classes (program_id, year_level, block) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $program_id, $year_level, $block);
        $stmt->execute();
        $class_id = $stmt->insert_id;
        $stmt->close();
        
        error_log("ADD CLASS - Created class with ID: $class_id");
        
        if (!empty($subjects)) {
            error_log("ADD CLASS - Adding " . count($subjects) . " subjects");
            $sub_stmt = $conn->prepare("INSERT INTO class_subjects (class_id, subject_id, faculty_id) VALUES (?, ?, ?)");
            foreach ($subjects as $subject_id) {
                $faculty_id = isset($faculty_assignments[$subject_id]) ? intval($faculty_assignments[$subject_id]) : 0;
                error_log("ADD CLASS - Adding subject $subject_id with faculty $faculty_id to class $class_id");
                // Allow faculty_id to be 0 for now, faculty can be assigned later
                $sub_stmt->bind_param("iii", $class_id, $subject_id, $faculty_id);
                $sub_stmt->execute();
                error_log("ADD CLASS - Successfully inserted subject $subject_id");
            }
            $sub_stmt->close();
        } else {
            error_log("ADD CLASS - No subjects to add");
        }

        syncRegularStudentsForClass($conn, intval($class_id));
        
        $conn->commit();
        error_log("ADD CLASS - Transaction committed successfully");
        echo json_encode(["status" => "success", "message" => "Class added successfully with ID: $class_id"]);
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("ADD CLASS - Transaction rolled back: " . $e->getMessage());
        echo json_encode(["status"=>"error","message"=>"Failed to add class: " . $e->getMessage()]);
    }
    
    exit;
}

/* ========================= EDIT CLASS ========================= */
if ($action === "edit") {
    $id = $_POST['id'] ?? null;
    $program_id = $_POST['program_id'] ?? null;
    $section_name = $_POST['section_name'] ?? "";
    $year_level = $_POST['year_level'] ?? "";
    $block = $_POST['block'] ?? "";
    
    $subjects = [];
    if (isset($_POST['subjects'])) {
        $subjects = json_decode($_POST['subjects'], true);
        if (!is_array($subjects)) {
            $subjects = [];
        }
    }

    $faculty_assignments = [];
    if (isset($_POST['faculty_assignments'])) {
        $faculty_assignments = json_decode($_POST['faculty_assignments'], true);
        if (!is_array($faculty_assignments)) {
            $faculty_assignments = [];
        }
    }
    
    if (!$id || !$program_id || !$year_level || !$block || empty($subjects)) {
        echo json_encode(["status"=>"error","message"=>"Missing required fields"]);
        exit;
    }
    
    // Check for duplicate class (same year level and section in same program), excluding current class
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM add_classes WHERE program_id = ? AND year_level = ? AND block = ? AND id != ?");
    $check_stmt->bind_param("issi", $program_id, $year_level, $block, $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    $check_stmt->close();
    
    if ($row['count'] > 0) {
        echo json_encode([
            "status" => "error", 
            "message" => "The faculty or subject already exist in this section"
        ]);
        exit;
    }
    
    $conn->begin_transaction();
    
    try {
        // Update class
        $stmt = $conn->prepare("UPDATE add_classes SET program_id = ?, year_level = ?, block = ? WHERE id = ?");
        $stmt->bind_param("issi", $program_id, $year_level, $block, $id);
        $stmt->execute();
        $stmt->close();
        
        // Delete existing class subjects
        $stmt = $conn->prepare("DELETE FROM class_subjects WHERE class_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        // Add new class subjects
        if (!empty($subjects)) {
            $sub_stmt = $conn->prepare("INSERT INTO class_subjects (class_id, subject_id, faculty_id) VALUES (?, ?, ?)");
            foreach ($subjects as $subject_id) {
                $faculty_id = isset($faculty_assignments[$subject_id]) ? intval($faculty_assignments[$subject_id]) : 0;
                // Allow faculty_id to be 0 for now, faculty can be assigned later
                $sub_stmt->bind_param("iii", $id, $subject_id, $faculty_id);
                $sub_stmt->execute();
            }
            $sub_stmt->close();
        }

        syncRegularStudentsForClass($conn, intval($id));
        
        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Class updated successfully"]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status"=>"error","message"=>"Failed to update class: " . $e->getMessage()]);
    }
    
    exit;
}

/* ========================= ADD SUBJECT TO CLASS ========================= */
if ($action === "add_subject_to_class") {
    $class_id = $_POST['class_id'] ?? 0;
    $subject_id = $_POST['subject_id'] ?? 0;
    $faculty_id = $_POST['faculty_id'] ?? 0;
    
    if (!$class_id || !$subject_id) {
        echo json_encode(["status" => "error", "message" => "Missing class ID or subject ID"]);
        exit;
    }

    // Prevent duplicate subject or duplicate faculty assignment in the same section/class
    $dup_stmt = $conn->prepare("
        SELECT id
        FROM class_subjects
        WHERE class_id = ?
          AND (subject_id = ? OR (faculty_id = ? AND ? != 0))
        LIMIT 1
    ");
    $dup_stmt->bind_param("iiii", $class_id, $subject_id, $faculty_id, $faculty_id);
    $dup_stmt->execute();
    $dup_res = $dup_stmt->get_result();
    if ($dup_res && $dup_res->num_rows > 0) {
        $dup_stmt->close();
        echo json_encode([
            "status" => "error",
            "message" => "The faculty or subject already exist in this section"
        ]);
        exit;
    }
    $dup_stmt->close();
    
    $stmt = $conn->prepare("INSERT INTO class_subjects (class_id, subject_id, faculty_id) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $class_id, $subject_id, $faculty_id);
    
    if ($stmt->execute()) {
        syncRegularStudentsForClass($conn, intval($class_id));
        echo json_encode(["status" => "success", "message" => "Subject added to class successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to add subject: " . $stmt->error]);
    }
    
    $stmt->close();
    exit;
}

/* ========================= DELETE SUBJECT FROM CLASS ========================= */
if ($action === "delete_subject") {
    error_log("DELETE_SUBJECT called");
    error_log("POST data: " . print_r($_POST, true));
    
    $class_id = $_POST['class_id'] ?? 0;
    $subject_id = $_POST['subject_id'] ?? 0;
    
    error_log("class_id: $class_id, subject_id: $subject_id");
    
    if (!$class_id || !$subject_id) {
        error_log("Missing IDs");
        echo json_encode(["status" => "error", "message" => "Missing class ID or subject ID"]);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM class_subjects WHERE class_id = ? AND subject_id = ?");
    $stmt->bind_param("ii", $class_id, $subject_id);
    
    if ($stmt->execute()) {
        error_log("Execute successful, affected_rows: " . $stmt->affected_rows);
        if ($stmt->affected_rows > 0) {
            syncRegularStudentsForClass($conn, intval($class_id));
            echo json_encode(["status" => "success", "message" => "Subject removed from class successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Subject not found in this class"]);
        }
    } else {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(["status" => "error", "message" => "Failed to remove subject: " . $stmt->error]);
    }
    
    $stmt->close();
    exit;
}

/* ========================= DELETE CLASS ========================= */
if ($action === "delete") {
    $id = $_POST['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(["status" => "error", "message" => "Missing class ID"]);
        exit;
    }
    
    $conn->begin_transaction();
    
    try {
        // Delete class subjects first
        $stmt = $conn->prepare("DELETE FROM class_subjects WHERE class_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        // Delete class
        $stmt = $conn->prepare("DELETE FROM add_classes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $conn->commit();
            echo json_encode(["status" => "success", "message" => "Class deleted successfully"]);
        } else {
            $conn->rollback();
            echo json_encode(["status" => "error", "message" => "Class not found"]);
        }
        $stmt->close();
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Failed to delete class: " . $e->getMessage()]);
    }
    
    exit;
}

echo json_encode(["status" => "error", "message" => "Invalid action"]);
?>

<?php
include "connect.php";

header("Content-Type: application/json");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$action = $_REQUEST['action'] ?? "";

function ensureSubjectSemesterColumn(mysqli $conn): void {
    $check = $conn->query("SHOW COLUMNS FROM add_subjects LIKE 'semester'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE add_subjects ADD COLUMN semester VARCHAR(20) NOT NULL DEFAULT '1st Semester' AFTER subject_desc");
    }
}

// Log received data for debugging
error_log("Action: " . $action);
error_log("POST data: " . print_r($_POST, true));

/* ========================= ADD SUBJECT ========================= */
if ($action === "add") {

    $program_id = $_POST['program_id'] ?? null;
    $code       = $_POST['subject_code'] ?? "";
    $desc       = $_POST['subject_desc'] ?? "";
    $semester   = $_POST['semester'] ?? "";
    $year_level = $_POST['year_level'] ?? "";

    error_log("Adding subject - Program ID: $program_id, Code: $code, Desc: $desc, Semester: $semester, Year: $year_level");

    if (!$program_id || !$code || !$desc || !$semester || !$year_level) {
        error_log("Missing fields detected");
        echo json_encode(["status"=>"error","message"=>"Missing fields"]);
        exit;
    }

    ensureSubjectSemesterColumn($conn);

    $stmt = $conn->prepare("
        INSERT INTO add_subjects (program_id, subject_code, subject_desc, semester, year_level)
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(["status"=>"error","message"=>"Database prepare failed"]);
        exit;
    }

    $stmt->bind_param("issss", $program_id, $code, $desc, $semester, $year_level);

    if ($stmt->execute()) {
        error_log("Subject added successfully");
        echo json_encode(["status" => "success", "message" => "Subject added successfully"]);
    } else {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(["status" => "error", "message" => "Failed to add subject: " . $stmt->error]);
    }

    $stmt->close();
    exit;
}

/* ========================= GET SUBJECTS ========================= */
if ($action === "get") {

    $program_id = $_GET['program_id'] ?? 0;

    ensureSubjectSemesterColumn($conn);

    $stmt = $conn->prepare("
        SELECT id, program_id, subject_code, subject_desc, semester, year_level
        FROM add_subjects
        WHERE program_id=?
        ORDER BY semester ASC, subject_code ASC
    ");

    $stmt->bind_param("i", $program_id);
    $stmt->execute();

    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}

/* ========================= DELETE SUBJECT ========================= */
if ($action === "delete") {

    $id = $_POST['id'] ?? 0;
    
    error_log("Attempting to delete subject with ID: $id");

    // Check if subject is assigned to any classes first
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM class_subjects WHERE subject_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    $check_stmt->close();
    
    if ($row['count'] > 0) {
        error_log("Subject is assigned to " . $row['count'] . " classes, cannot delete");
        echo json_encode([
            "status" => "error", 
            "message" => "Cannot delete subject. It is currently assigned to " . $row['count'] . " class(es). Please remove it from all classes first."
        ]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM add_subjects WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        error_log("Subject deleted successfully");
        echo json_encode([
            "status" => "success",
            "message" => "Subject deleted successfully"
        ]);
    } else {
        error_log("Failed to delete subject: " . $stmt->error);
        echo json_encode([
            "status" => "error",
            "message" => "Failed to delete subject: " . $stmt->error
        ]);
    }

    $stmt->close();
    exit;
}

/* ========================= EDIT SUBJECT ========================= */
if ($action === "edit") {

    $id         = $_POST['id'] ?? 0;
    $code       = $_POST['subject_code'] ?? "";
    $desc       = $_POST['subject_desc'] ?? "";
    $semester   = $_POST['semester'] ?? "";
    $year_level = $_POST['year_level'] ?? "";

    if (!$id || !$code || !$desc || !$semester || !$year_level) {
        echo json_encode(["status"=>"error","message"=>"Missing fields"]);
        exit;
    }

    ensureSubjectSemesterColumn($conn);

    $stmt = $conn->prepare("
        UPDATE add_subjects 
        SET subject_code=?, subject_desc=?, semester=?, year_level=?
        WHERE id=?
    ");

    $stmt->bind_param("ssssi", $code, $desc, $semester, $year_level, $id);

    echo json_encode([
        "status" => $stmt->execute() ? "success" : "error"
    ]);

    exit;
}

/* ========================= GET BY YEAR (FIXED) ========================= */
if ($action === "get_by_year") {

    header("Content-Type: application/json");

    $program_id = $_GET['program_id'] ?? 0;
    $year_level = $_GET['year_level'] ?? '';

    ensureSubjectSemesterColumn($conn);

    $stmt = $conn->prepare("
        SELECT id, subject_code, subject_desc, semester, year_level
        FROM add_subjects
        WHERE program_id = ? AND year_level = ?
    ");

    $stmt->bind_param("is", $program_id, $year_level);
    $stmt->execute();

    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}

/* ========================= GET ALL SUBJECTS ========================= */
if ($action === "get_all") {

    header("Content-Type: application/json");

    ensureSubjectSemesterColumn($conn);

    $stmt = $conn->prepare("
        SELECT s.id, s.subject_code, s.subject_desc, s.semester, s.year_level, p.program_name
        FROM add_subjects s
        LEFT JOIN add_programs p ON s.program_id = p.id
        ORDER BY p.program_name ASC, s.semester ASC, s.year_level ASC, s.subject_code ASC
    ");

    $stmt->execute();

    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}

/* ========================= GET ALL SUBJECTS FOR PROGRAM ========================= */
if ($action === "get_all_by_program") {

    header("Content-Type: application/json");

    $program_id = $_GET['program_id'] ?? 0;

    ensureSubjectSemesterColumn($conn);

    $stmt = $conn->prepare("
        SELECT id, subject_code, subject_desc, semester, year_level
        FROM add_subjects
        WHERE program_id = ?
        ORDER BY semester ASC, year_level ASC, subject_code ASC
    ");

    $stmt->bind_param("i", $program_id);
    $stmt->execute();

    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}

/* ========================= GET SUBJECTS BY PROGRAM AND YEAR LEVEL ========================= */
if ($action === "get_by_program_and_year") {

    header("Content-Type: application/json");

    $program_id = $_GET['program_id'] ?? 0;
    $year_level = $_GET['year_level'] ?? "";

    if (!$program_id || !$year_level) {
        echo json_encode(["status" => "error", "message" => "Program ID and year level are required"]);
        exit;
    }

    ensureSubjectSemesterColumn($conn);

    $stmt = $conn->prepare("
        SELECT id, subject_code, subject_desc, semester, year_level
        FROM add_subjects
        WHERE program_id = ? AND year_level = ?
        ORDER BY semester ASC, subject_code ASC
    ");

    $stmt->bind_param("is", $program_id, $year_level);
    $stmt->execute();

    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}

/* ========================= GET SUBJECTS AUTOMATICALLY FOR STUDENTS ========================= */
if ($action === "get_auto_subjects") {

    header("Content-Type: application/json");

    $program_id = $_GET['program_id'] ?? 0;
    $year_level = $_GET['year_level'] ?? "";

    if (!$program_id || !$year_level) {
        echo json_encode(["status" => "error", "message" => "Program ID and year level are required"]);
        exit;
    }

    ensureSubjectSemesterColumn($conn);

    $stmt = $conn->prepare("
        SELECT id, subject_code, subject_desc, semester, year_level
        FROM add_subjects
        WHERE program_id = ? AND year_level = ?
        ORDER BY semester ASC, subject_code ASC
    ");

    $stmt->bind_param("is", $program_id, $year_level);
    $stmt->execute();

    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        "status" => "success", 
        "subjects" => $data,
        "count" => count($data)
    ]);
    exit;
}

/* ========================= DEFAULT ========================= */
echo json_encode([
    "status" => "error",
    "message" => "Invalid action"
]);
exit;
?>

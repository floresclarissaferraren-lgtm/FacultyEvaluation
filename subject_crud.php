<?php
include "connect.php";

header("Content-Type: application/json");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$action = $_REQUEST['action'] ?? "";

// Log received data for debugging
error_log("Action: " . $action);
error_log("POST data: " . print_r($_POST, true));

/* ========================= ADD SUBJECT ========================= */
if ($action === "add") {

    $program_id = $_POST['program_id'] ?? null;
    $code       = $_POST['subject_code'] ?? "";
    $desc       = $_POST['subject_desc'] ?? "";
    $year_level = $_POST['year_level'] ?? "";

    error_log("Adding subject - Program ID: $program_id, Code: $code, Desc: $desc, Year: $year_level");

    if (!$program_id || !$code || !$desc || !$year_level) {
        error_log("Missing fields detected");
        echo json_encode(["status"=>"error","message"=>"Missing fields"]);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO add_subjects (program_id, subject_code, subject_desc, year_level)
        VALUES (?, ?, ?, ?)
    ");

    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(["status"=>"error","message"=>"Database prepare failed"]);
        exit;
    }

    $stmt->bind_param("isss", $program_id, $code, $desc, $year_level);

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

    $stmt = $conn->prepare("
        SELECT id, program_id, subject_code, subject_desc, year_level
        FROM add_subjects
        WHERE program_id=?
        ORDER BY subject_code ASC
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

    $stmt = $conn->prepare("DELETE FROM add_subjects WHERE id=?");
    $stmt->bind_param("i", $id);

    echo json_encode([
        "status" => $stmt->execute() ? "success" : "error"
    ]);

    exit;
}

/* ========================= EDIT SUBJECT ========================= */
if ($action === "edit") {

    $id         = $_POST['id'] ?? 0;
    $code       = $_POST['subject_code'] ?? "";
    $desc       = $_POST['subject_desc'] ?? "";
    $year_level = $_POST['year_level'] ?? "";

    if (!$id || !$code || !$desc || !$year_level) {
        echo json_encode(["status"=>"error","message"=>"Missing fields"]);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE add_subjects 
        SET subject_code=?, subject_desc=?, year_level=?
        WHERE id=?
    ");

    $stmt->bind_param("sssi", $code, $desc, $year_level, $id);

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

    $stmt = $conn->prepare("
        SELECT id, subject_code, subject_desc, year_level
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

    $stmt = $conn->prepare("
        SELECT s.id, s.subject_code, s.subject_desc, s.year_level, p.program_name
        FROM add_subjects s
        LEFT JOIN add_programs p ON s.program_id = p.id
        ORDER BY p.program_name ASC, s.year_level ASC, s.subject_code ASC
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

    $stmt = $conn->prepare("
        SELECT id, subject_code, subject_desc, year_level
        FROM add_subjects
        WHERE program_id = ?
        ORDER BY year_level ASC, subject_code ASC
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

/* ========================= DEFAULT ========================= */
echo json_encode([
    "status" => "error",
    "message" => "Invalid action"
]);
exit;
?>
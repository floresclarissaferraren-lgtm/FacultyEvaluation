<?php
include "connect.php";

header("Content-Type: application/json");

$action = $_REQUEST['action'] ?? "";

/* ========================= ADD SUBJECT ========================= */
if ($action === "add") {

    $program_id = $_POST['program_id'] ?? null;
    $code       = $_POST['subject_code'] ?? "";
    $desc       = $_POST['subject_desc'] ?? "";
    $year_level = $_POST['year_level'] ?? "";

    if (!$program_id || !$code || !$desc || !$year_level) {
        echo json_encode(["status"=>"error","message"=>"Missing fields"]);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO add_subjects (program_id, subject_code, subject_desc, year_level)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param("isss", $program_id, $code, $desc, $year_level);

    echo json_encode([
        "status" => $stmt->execute() ? "success" : "error"
    ]);

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

/* ========================= DEFAULT ========================= */
echo json_encode([
    "status" => "error",
    "message" => "Invalid action"
]);
exit;
?>
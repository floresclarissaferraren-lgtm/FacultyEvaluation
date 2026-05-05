<?php
include 'connect.php';

// 🔥 IMPORTANT: set JSON header
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id   = $_POST['id'];
    $code = $_POST['program_code'];
    $name = $_POST['program_name'];

    // basic validation
    if (empty($code) || empty($name)) {
        echo json_encode([
            "status" => "error",
            "message" => "Program code and name are required."
        ]);
        exit;
    }

    // check if program code already exists (excluding current record, case-insensitive)
    $check = $conn->prepare("SELECT id FROM add_programs WHERE LOWER(program_code) = LOWER(?) AND id != ?");
    $check->bind_param("si", $code, $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Program Code already exists"
        ]);
        $check->close();
        exit;
    }

    $check->close();

    // Convert program code to uppercase for consistency
    $code = strtoupper($code);
    
    $stmt = $conn->prepare("UPDATE add_programs SET program_code=?, program_name=? WHERE id=?");
    $stmt->bind_param("ssi", $code, $name, $id);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "id" => $id,
            "program_code" => $code,
            "program_name" => $name
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => $stmt->error
        ]);
    }

    $stmt->close();
    $conn->close();
}
?>

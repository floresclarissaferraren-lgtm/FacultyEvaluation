<?php
include "connect.php";
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $program_code = $_POST['program_code'] ?? '';

    if (empty($program_code)) {
        echo json_encode(['success' => false, 'error' => 'Missing program_code']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM add_programs WHERE program_code = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Prepare failed']);
        exit;
    }

    $stmt->bind_param("s", $program_code);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'deleted_code' => $program_code]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No record found']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>

<?php
include "connect.php";

header('Content-Type: text/plain'); // simple response

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $program_code = $_POST['program_code'] ?? '';

    if (empty($program_code)) {
        echo "error: missing program_code";
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM add_programs WHERE program_code = ?");
    
    if (!$stmt) {
        echo "error: prepare failed";
        exit;
    }

    $stmt->bind_param("s", $program_code);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "success";
        } else {
            echo "error: no record found";
        }
    } else {
        echo "error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
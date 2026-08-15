<?php
include 'connect.php';

// 🔥 IMPORTANT: set JSON header
header('Content-Type: application/json');

function normalizeProgramCodeInput(string $code): string {
    $code = trim($code);
    $code = preg_replace('/\\\\[rnt]/i', '', $code);
    $code = preg_replace('/\s+/', '', $code);
    return strtoupper($code);
}

function programCodeExists(mysqli $conn, string $normalizedCode, int $excludeId): bool {
    $result = $conn->query("SELECT id, program_code FROM add_programs");
    if (!$result) return false;

    while ($row = $result->fetch_assoc()) {
        if (intval($row['id']) === $excludeId) {
            continue;
        }
        if (normalizeProgramCodeInput((string)$row['program_code']) === $normalizedCode) {
            return true;
        }
    }

    return false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id   = intval($_POST['id'] ?? 0);
    $code = normalizeProgramCodeInput($_POST['program_code'] ?? '');
    $name = trim($_POST['program_name'] ?? '');

    // basic validation
    if (empty($code) || empty($name)) {
        echo json_encode([
            "status" => "error",
            "message" => "Program code and name are required."
        ]);
        exit;
    }

    if (programCodeExists($conn, $code, $id)) {
        echo json_encode([
            "status" => "error",
            "message" => "Program code already exists. Please use a different program code."
        ]);
        exit;
    }
    
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

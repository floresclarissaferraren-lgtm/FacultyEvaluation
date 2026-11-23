<?php
require_once __DIR__ . "/../shared/security.php";
requireRole("admin");
requireMethod("POST");
include __DIR__ . "/../shared/connect.php";
require_once __DIR__ . "/../shared/department_schema.php";

// 🔥 IMPORTANT: set JSON header
header("Content-Type: application/json");
ensureDepartmentSchema($conn);

function normalizeProgramCodeInput(string $code): string
{
    $code = trim($code);
    $code = preg_replace("/\\\\[rnt]/i", "", $code);
    $code = preg_replace("/\s+/", "", $code);
    return strtoupper($code);
}

function programCodeExists(
    mysqli $conn,
    string $normalizedCode,
    int $excludeId,
): bool {
    $result = $conn->query("SELECT id, program_code FROM add_programs");
    if (!$result) {
        return false;
    }

    while ($row = $result->fetch_assoc()) {
        if (intval($row["id"]) === $excludeId) {
            continue;
        }
        if (
            normalizeProgramCodeInput((string) $row["program_code"]) ===
            $normalizedCode
        ) {
            return true;
        }
    }

    return false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST["id"] ?? 0);
    $code = normalizeProgramCodeInput($_POST["program_code"] ?? "");
    $name = trim($_POST["program_name"] ?? "");
    $departmentValue = trim((string) ($_POST["department_id"] ?? ""));
    $departmentId =
        $departmentValue === "" ? null : filter_var($departmentValue, FILTER_VALIDATE_INT);

    // basic validation
    if (empty($code) || empty($name) || $id <= 0 || $departmentId === false) {
        echo json_encode([
            "status" => "error",
            "message" => "A valid department, program code, name, and program are required.",
        ]);
        exit();
    }

    if ($departmentId !== null) {
        $department = $conn->prepare("SELECT id FROM add_departments WHERE id = ? LIMIT 1");
        $department->bind_param("i", $departmentId);
        $department->execute();
        if ($department->get_result()->num_rows === 0) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Select a valid department.",
            ]);
            exit();
        }
        $department->close();
    }

    if (programCodeExists($conn, $code, $id)) {
        echo json_encode([
            "status" => "error",
            "message" =>
                "Program code already exists. Please use a different program code.",
        ]);
        exit();
    }

    $stmt = $conn->prepare(
        "UPDATE add_programs SET department_id=?, program_code=?, program_name=? WHERE id=?",
    );
    $stmt->bind_param("issi", $departmentId, $code, $name, $id);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "id" => $id,
            "department_id" => $departmentId,
            "program_code" => $code,
            "program_name" => $name,
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => $stmt->error,
        ]);
    }

    $stmt->close();
    $conn->close();
}
?>

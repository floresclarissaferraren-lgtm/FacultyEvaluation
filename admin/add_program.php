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

function programCodeExists(mysqli $conn, string $normalizedCode): bool
{
    $result = $conn->query("SELECT program_code FROM add_programs");
    if (!$result) {
        return false;
    }

    while ($row = $result->fetch_assoc()) {
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
    $code = normalizeProgramCodeInput($_POST["program_code"] ?? "");
    $name = trim($_POST["program_name"] ?? "");
    $departmentId = filter_var(
        $_POST["department_id"] ?? null,
        FILTER_VALIDATE_INT,
    ) ?: 0;

    // basic validation
    if (empty($code) || empty($name) || $departmentId <= 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Department, program code, and name are required.",
        ]);
        exit();
    }

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

    if (programCodeExists($conn, $code)) {
        echo json_encode([
            "status" => "error",
            "message" =>
                "Program code already exists. Please use a different program code.",
        ]);
        exit();
    }

    $stmt = $conn->prepare(
        "INSERT INTO add_programs (department_id, program_code, program_name) VALUES (?, ?, ?)",
    );
    $stmt->bind_param("iss", $departmentId, $code, $name);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "id" => $conn->insert_id,
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

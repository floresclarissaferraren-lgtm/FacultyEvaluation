<?php
require_once __DIR__ . "/../shared/security.php";
requireRole("admin");
requireMethod("POST");
require_once __DIR__ . "/../shared/connect.php";
require_once __DIR__ . "/../shared/department_schema.php";

header("Content-Type: application/json");

ensureDepartmentSchema($conn);

$action = trim($_POST["action"] ?? "");
$id = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT) ?: 0;
$code = strtoupper(preg_replace("/\s+/", "", trim($_POST["department_code"] ?? "")));
$name = trim($_POST["department_name"] ?? "");

if ($action === "create" || $action === "update") {
    if ($code === "" || $name === "") {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Department code and name are required.",
        ]);
        exit();
    }

    $duplicate = $conn->prepare(
        "SELECT id FROM add_departments WHERE UPPER(TRIM(department_code)) = ? AND id <> ? LIMIT 1",
    );
    $duplicate->bind_param("si", $code, $id);
    $duplicate->execute();
    if ($duplicate->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode([
            "status" => "error",
            "message" => "Department code already exists.",
        ]);
        exit();
    }
    $duplicate->close();

    if ($action === "create") {
        $stmt = $conn->prepare(
            "INSERT INTO add_departments (department_code, department_name) VALUES (?, ?)",
        );
        $stmt->bind_param("ss", $code, $name);
    } else {
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "A valid department is required.",
            ]);
            exit();
        }
        $stmt = $conn->prepare(
            "UPDATE add_departments SET department_code = ?, department_name = ? WHERE id = ?",
        );
        $stmt->bind_param("ssi", $code, $name, $id);
    }

    $stmt->execute();
    if ($action === "update" && $stmt->affected_rows === 0) {
        $exists = $conn->prepare("SELECT id FROM add_departments WHERE id = ? LIMIT 1");
        $exists->bind_param("i", $id);
        $exists->execute();
        $found = $exists->get_result()->num_rows > 0;
        $exists->close();
        if (!$found) {
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "Department not found.",
            ]);
            exit();
        }
    }

    echo json_encode([
        "status" => "success",
        "id" => $action === "create" ? $conn->insert_id : $id,
        "department_code" => $code,
        "department_name" => $name,
    ]);
    $stmt->close();
    exit();
}

if ($action === "delete") {
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "A valid department is required.",
        ]);
        exit();
    }

    $programs = $conn->prepare(
        "SELECT COUNT(*) AS total FROM add_programs WHERE department_id = ?",
    );
    $programs->bind_param("i", $id);
    $programs->execute();
    $programCount = (int) $programs->get_result()->fetch_assoc()["total"];
    $programs->close();

    if ($programCount > 0) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "error" => "Move or delete its programs before deleting this department.",
        ]);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM add_departments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "error" => "Department not found.",
        ]);
        exit();
    }

    echo json_encode(["success" => true]);
    $stmt->close();
    exit();
}

http_response_code(400);
echo json_encode([
    "status" => "error",
    "message" => "Invalid department action.",
]);

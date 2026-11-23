<?php
require_once __DIR__ . "/../shared/security.php";
requireRole("admin");
require_once __DIR__ . "/../shared/connect.php";
require_once __DIR__ . "/../shared/department_schema.php";

header("Content-Type: application/json");

ensureDepartmentSchema($conn);
$result = $conn->query("
    SELECT d.id, d.department_code, d.department_name, COUNT(p.id) AS program_count
    FROM add_departments d
    LEFT JOIN add_programs p ON p.department_id = d.id
    GROUP BY d.id, d.department_code, d.department_name
    ORDER BY d.department_name ASC, d.department_code ASC
");

$departments = [];
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

$unassignedResult = $conn->query(
    "SELECT COUNT(*) AS total FROM add_programs WHERE department_id IS NULL",
);
$unassignedProgramCount = (int) $unassignedResult->fetch_assoc()["total"];

echo json_encode([
    "departments" => $departments,
    "unassigned_program_count" => $unassignedProgramCount,
]);

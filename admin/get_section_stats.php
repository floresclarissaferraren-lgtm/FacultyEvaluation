<?php
require_once __DIR__ . "/../shared/security.php";
requireRole("admin");
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include __DIR__ . "/../shared/connect.php";
require_once __DIR__ . "/../shared/ensure_schema_column.php";
require_once __DIR__ . "/../shared/dashboard_stats_helper.php";

ensureColumnExists(
    $conn,
    "add_faculties",
    "status",
    'VARCHAR(20) NOT NULL DEFAULT "active"',
);
ensureColumnExists(
    $conn,
    "add_students",
    "status",
    'VARCHAR(20) NOT NULL DEFAULT "active"',
);

$stats = [
    "faculty" => getFacultyStatistics($conn),
    "students" => getStudentStatistics($conn),
];

echo json_encode(["success" => true, "data" => $stats]);
$conn->close();
?>

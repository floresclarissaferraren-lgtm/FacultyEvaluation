<?php
include __DIR__ . "/../shared/connect.php";
require_once __DIR__ . "/../shared/ensure_schema_column.php";
require_once __DIR__ . "/../shared/dashboard_stats_helper.php";

ensureColumnExists(
    $conn,
    "add_students",
    "status",
    'VARCHAR(20) NOT NULL DEFAULT "active"',
);
ensureColumnExists(
    $conn,
    "add_faculties",
    "status",
    'VARCHAR(20) NOT NULL DEFAULT "active"',
);

$totalFacultyCount = scalarCount(
    $conn,
    "SELECT COUNT(*) AS total FROM add_faculties WHERE LOWER(TRIM(COALESCE(status, 'active'))) = 'active'",
);
$totalStudentsCount = scalarCount(
    $conn,
    "SELECT COUNT(*) AS total FROM add_students WHERE LOWER(TRIM(COALESCE(status, 'active'))) = 'active'",
);
$totalEvaluationsCount = 0;
$totalProgramsCount = getTotalCount($conn, "add_programs");

$studentStats = getStudentStatistics($conn);
$facultyStats = getFacultyStatistics($conn);

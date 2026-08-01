<?php
include 'connect.php';
require_once 'ensure_schema_column.php';
require_once 'dashboard_stats_helper.php';

ensureColumnExists($conn, 'add_students', 'status', 'VARCHAR(20) NOT NULL DEFAULT "active"');
ensureColumnExists($conn, 'add_faculties', 'status', 'VARCHAR(20) NOT NULL DEFAULT "active"');

$totalFacultyCount = getTotalCount($conn, 'add_faculties');
$totalStudentsCount = getTotalCount($conn, 'add_students');
$totalEvaluationsCount = 0;
$totalProgramsCount = getTotalCount($conn, 'add_programs');

$studentStats = getStudentStatistics($conn);
$facultyStats = getFacultyStatistics($conn);

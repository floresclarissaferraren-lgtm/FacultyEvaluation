<?php
require_once 'ensure_schema_column.php';

function scalarCount(mysqli $conn, string $sql): int {
    $result = $conn->query($sql);
    if (!$result) {
        return 0;
    }
    $row = $result->fetch_assoc();
    return isset($row['total']) ? (int)$row['total'] : 0;
}

function getTotalCount(mysqli $conn, string $table): int {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM {$table}");
    if (!$stmt) {
        return 0;
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        return 0;
    }
    $row = $result->fetch_assoc();
    $stmt->close();
    return isset($row['total']) ? (int)$row['total'] : 0;
}

function getStudentStatistics(mysqli $conn): array {
    $stats = [];

    $stats['total_students'] = scalarCount($conn, "SELECT COUNT(*) AS total FROM add_students");
    $stats['active_students'] = scalarCount($conn, "SELECT COUNT(*) AS total FROM add_students WHERE LOWER(TRIM(status)) = 'active'");
    $stats['regular_students'] = scalarCount($conn, "SELECT COUNT(*) AS total FROM add_students WHERE student_type = 'regular' AND LOWER(TRIM(COALESCE(status, 'active'))) = 'active'");
    $stats['irregular_students'] = scalarCount($conn, "SELECT COUNT(*) AS total FROM add_students WHERE student_type = 'irregular' AND LOWER(TRIM(COALESCE(status, 'active'))) = 'active'");

    return $stats;
}

function getFacultyStatistics(mysqli $conn): array {
    $stats = [];

    $stats['total_faculty'] = scalarCount($conn, "SELECT COUNT(*) AS total FROM add_faculties");
    $stats['active_faculty'] = scalarCount($conn, "SELECT COUNT(*) AS total FROM add_faculties WHERE LOWER(TRIM(status)) = 'active'");
    $stats['pending_evaluation'] = scalarCount($conn, "
        SELECT COUNT(DISTINCT f.id) AS total
        FROM add_faculties f
        LEFT JOIN evaluations e ON f.id = e.faculty_id
        WHERE e.faculty_id IS NULL
          AND LOWER(TRIM(COALESCE(f.status, 'active'))) = 'active'
    ");

    return $stats;
}

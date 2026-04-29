<?php
include 'connect.php';

function getTotalCount($conn, $table) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM {$table}");
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
    return isset($row['total']) ? (int) $row['total'] : 0;
}

$totalFacultyCount = getTotalCount($conn, 'add_faculties');
$totalStudentsCount = getTotalCount($conn, 'add_students');
$totalEvaluationsCount = 0; 
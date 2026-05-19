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
// Used by "Total Evaluations" dashboard card: total active students who need to evaluate.
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM add_students WHERE LOWER(TRIM(COALESCE(status,'active'))) = 'active'");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $totalEvaluationsCount = $row && isset($row['total']) ? (int) $row['total'] : 0;
    $stmt->close();
} else {
    $totalEvaluationsCount = 0;
}

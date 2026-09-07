<?php
/**
 * get_uploaded_reports.php
 *
 * API endpoint to fetch evaluation PDF reports for the logged-in faculty.
 */

require_once 'security.php';
requireRole('faculty');
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'faculty' || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

include 'connect.php';

$faculty_id = intval($_SESSION['id']);

$stmt = $conn->prepare("
    SELECT id, academic_year, semester, file_name, uploaded_at 
    FROM faculty_uploaded_reports 
    WHERE faculty_id = ? 
    ORDER BY uploaded_at DESC
");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit;
}

$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$res = $stmt->get_result();
$reports = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'reports' => $reports
]);
exit;
?>

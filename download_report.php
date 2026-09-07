<?php
/**
 * download_report.php
 *
 * Securely downloads an uploaded PDF report after verifying session permissions.
 */

require_once 'security.php';
requireRole('admin', 'faculty');

$session_id = intval($_SESSION['id']);
$role = $_SESSION['role'];

$report_id = intval($_GET['id'] ?? 0);
if ($report_id <= 0) {
    http_response_code(400);
    exit('Invalid report ID.');
}

include 'connect.php';

$stmt = $conn->prepare("SELECT faculty_id, file_path, file_name FROM faculty_uploaded_reports WHERE id = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    exit('Database error.');
}

$stmt->bind_param("i", $report_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$report) {
    http_response_code(404);
    exit('Report not found.');
}

// Security restriction: Admins can download anything, faculty can only download their own reports
if ($role !== 'admin' && intval($report['faculty_id']) !== $session_id) {
    http_response_code(403);
    exit('Access denied. You can only download your own reports.');
}

$file = $report['file_path'];
$reportDirectory = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'evaluation_reports');
$resolvedFile = realpath($file);
if ($reportDirectory === false || $resolvedFile === false || !is_file($resolvedFile)
    || strpos($resolvedFile, $reportDirectory . DIRECTORY_SEPARATOR) !== 0) {
    http_response_code(404);
    exit('File not found on server.');
}
$file = $resolvedFile;

// Send file headers and stream the PDF file
header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($report['file_name']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file));

// Clear output buffers to prevent file corruption
ob_clean();
flush();

readfile($file);
exit;
?>

<?php
ob_start();
require_once __DIR__ . "/../shared/security.php";
requireRole("faculty");
include __DIR__ . "/../shared/connect.php";
require_once __DIR__ . "/../shared/evaluation_period_helper.php";

date_default_timezone_set("Asia/Manila");

$data = json_decode(file_get_contents("php://input"), true);
$periodId = is_array($data) ? intval($data["period_id"] ?? 0) : 0;
$facultyId = intval($_SESSION["id"] ?? 0);

if ($facultyId <= 0 || $periodId <= 0) {
    http_response_code(400);
    exit("Faculty and evaluation period are required.");
}

if (isEvaluationOngoing($conn)) {
    http_response_code(403);
    exit(
        "Evaluation results are unavailable while the evaluation process is still ongoing."
    );
}

$facultyStmt = $conn->prepare("
    SELECT faculty_id, firstname, lastname, suffix, status
    FROM add_faculties
    WHERE id = ?
    LIMIT 1
");
$facultyStmt->bind_param("i", $facultyId);
$facultyStmt->execute();
$faculty = $facultyStmt->get_result()->fetch_assoc();
$facultyStmt->close();

if (
    !$faculty ||
    strtolower((string) ($faculty["status"] ?? "active")) !== "active"
) {
    http_response_code(403);
    exit("An active faculty account is required to generate this report.");
}

$periodStmt = $conn->prepare("
    SELECT id
    FROM evaluation_periods
    WHERE id = ? AND is_active = 0
      AND EXISTS (
          SELECT 1
          FROM evaluations e
          WHERE e.faculty_id = ?
            AND (
                e.period_id = evaluation_periods.id
                OR (
                    e.period_id IS NULL
                    AND evaluation_periods.start_date IS NOT NULL
                    AND evaluation_periods.end_date IS NOT NULL
                    AND DATE(e.created_at) BETWEEN evaluation_periods.start_date AND evaluation_periods.end_date
                )
            )
      )
    LIMIT 1
");
$periodStmt->bind_param("ii", $periodId, $facultyId);
$periodStmt->execute();
$periodExists = (bool) $periodStmt->get_result()->fetch_assoc();
$periodStmt->close();

if (!$periodExists) {
    http_response_code(404);
    exit("Evaluation period not found.");
}

$facultyName = trim(
    (string) $faculty["firstname"] .
        " " .
        (string) $faculty["lastname"] .
        " " .
        (string) ($faculty["suffix"] ?? ""),
);
$reportData = helperGetFacultyPerSubjectReportData(
    $conn,
    $facultyId,
    $periodId,
    $facultyName,
    (string) $faculty["faculty_id"],
);

if (!$reportData) {
    http_response_code(500);
    exit("Unable to load the faculty evaluation report.");
}

$temporaryPath = tempnam(sys_get_temp_dir(), "faculty-report-");
if ($temporaryPath === false) {
    http_response_code(500);
    exit("Unable to prepare the faculty evaluation PDF.");
}

try {
    $generated = helperGenerateReportPDF($conn, $reportData, $temporaryPath);
    $pdf = $generated ? file_get_contents($temporaryPath) : false;
} finally {
    if (is_file($temporaryPath)) {
        unlink($temporaryPath);
    }
}

if ($pdf === false || $pdf === "" || substr($pdf, 0, 5) !== "%PDF-") {
    http_response_code(500);
    exit(
        $generated
            ? "The faculty evaluation service generated an invalid PDF."
            : "Unable to generate the faculty evaluation PDF."
    );
}

while (ob_get_level() > 0) {
    ob_end_clean();
}
header("Content-Type: application/pdf");
header(
    'Content-Disposition: attachment; filename="Per_Subject_Report_' .
        preg_replace("/[^A-Za-z0-9._-]+/", "_", $facultyName) .
        "_" .
        date("Y-m-d") .
        '.pdf"',
);
header("Cache-Control: private, no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
echo $pdf;

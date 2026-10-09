<?php
include_once 'session_config.php';
session_start();
include 'connect.php';
require_once 'evaluation_period_helper.php';

if (isset($_SESSION['role']) && $_SESSION['role'] === 'faculty' && isEvaluationOngoing($conn)) {
    http_response_code(403);
    exit('Evaluation results are unavailable while the evaluation process is still ongoing.');
}

// Get JSON data, with GET fallback for direct URL access/testing.
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || empty($data)) {
    $data = $_GET;
}

// Validate required fields from either JSON or GET.
if (
    !isset($data['facultyName']) ||
    !isset($data['facultyId']) ||
    trim((string)$data['facultyName']) === '' ||
    trim((string)$data['facultyId']) === ''
) {
    http_response_code(400);
    exit('Missing required data');
}

// Load FPDF once
if (!class_exists('FPDF', false)) {
    require_once __DIR__ . '/fpdf.php';
}

// Fix font path for Windows
define('FPDF_FONTPATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'font' . DIRECTORY_SEPARATOR);

// Fix UTF-8 text for FPDF
function cleanText($text) {
    return iconv('UTF-8', 'windows-1252//TRANSLIT', $text);
}

// Safe values
$facultyName   = cleanText($data['facultyName']);
$facultyId     = cleanText($data['facultyId']);
$overallRating = cleanText($data['overallRating'] ?? '0.00');
$totalResponses = cleanText($data['totalResponses'] ?? '0');
$feedback      = cleanText($data['feedback'] ?? 'No feedback available');
$evaluationDetails = is_array($data['evaluationDetails'] ?? null) ? $data['evaluationDetails'] : [];
$allFeedback = cleanText($data['allFeedback'] ?? ($data['feedback'] ?? 'No feedback available'));
$evaluationPeriod = cleanText($data['evaluationPeriod'] ?? '');
$academicYear = cleanText($data['academicYear'] ?? '');
$semester = cleanText($data['semester'] ?? '');
$programFilter = cleanText($data['programFilter'] ?? '');
$subjectLabel = cleanText($data['subjectLabel'] ?? '');
$subjectContext = $subjectLabel;

$isFacultyReport = ($data['reportType'] ?? '') === 'faculty';
$isAdminRequest = !$isFacultyReport && (empty($data['feedback']) || $data['feedback'] === '');

// Get faculty_id from add_faculties table
$facultyIdDisplay = $facultyId; // Default to original ID
$stmt = $conn->prepare("SELECT faculty_id FROM add_faculties WHERE id = ?");
$stmt->bind_param("i", $facultyId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $facultyIdDisplay = $row['faculty_id'];
}
$stmt->close();

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();

// Set colors
$headerColor = array(70, 130, 180); // Steel blue
$borderColor = array(200, 200, 200); // Light gray
$textColor = array(50, 50, 50); // Dark gray

// =====================
// CALCULATE HEADER DIMENSIONS
// =====================
$logoWidth = 25;
$logoHeight = 25;
$padding = 10;

$pdf->SetFont('Arial', 'B', 18);
$collegeName = 'Granby Colleges of Science and Technology';
$collegeNameWidth = $pdf->GetStringWidth($collegeName);

$pdf->SetFont('Arial', 'B', 14);
$reportTitle = $isAdminRequest ? 'Individual Performance Report' : 'Faculty Evaluation Report';
$reportTitleWidth = $pdf->GetStringWidth($reportTitle);

$maxTextWidth = max($collegeNameWidth, $reportTitleWidth);
$headerWidth = $logoWidth + 15 + $maxTextWidth + 20; // logo + spacing + text + padding
$headerHeight = 40;
$headerX = (210 - $headerWidth) / 2; // Center horizontally
$headerY = 10;

// =====================
// HEADER BACKGROUND
// =====================
$pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
$pdf->Rect($headerX, $headerY, $headerWidth, $headerHeight, 'F');

// =====================
// LOGO
// =====================
if (file_exists('logo.png')) {
    $pdf->Image('logo.png', $headerX + 10, $headerY + 7, $logoWidth);
}

// =====================
// HEADER TEXT
// =====================
$pdf->SetTextColor(255, 255, 255); // White text

// College name - centered
$pdf->SetFont('Arial', 'B', 18);
$pdf->SetXY($headerX + $logoWidth + 15, $headerY + 8);
$pdf->Cell($maxTextWidth, 12, $collegeName, 0, 1, 'C');

// Report title - centered under college name (subtitle)
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetX($headerX + $logoWidth + 15);
$pdf->Cell($maxTextWidth, 10, $reportTitle, 0, 1, 'C');

$pdf->Ln(15);

// Reset text color for content
$pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);

// =====================
// FACULTY DETAILS BOX
// =====================
$boxX = 15; // Left margin
$boxWidth = 180; // Full width minus margins
$detailRows = [
    ['Name:', $facultyName],
    ['Faculty ID:', $facultyIdDisplay],
];
if ($academicYear !== '') {
    $detailRows[] = ['Academic Year:', $academicYear];
}
if ($semester !== '') {
    $detailRows[] = ['Semester:', $semester];
}
if ($evaluationPeriod !== '') {
    $detailRows[] = ['Evaluation Period:', $evaluationPeriod];
}
if ($subjectContext !== '') {
    $detailRows[] = ['Subject:', $subjectContext];
}
if ($programFilter !== '') {
    $detailRows[] = ['Program:', $programFilter];
}
$detailRows[] = ['Overall Rating:', $overallRating . ' / 5.00'];
$detailRows[] = ['Total Responses:', $totalResponses];
$boxHeight = 10 + (count($detailRows) * 10);
$boxY = 60;

$pdf->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);
$pdf->SetFillColor(248, 248, 248); // Light background
$pdf->Rect($boxX, $boxY, $boxWidth, $boxHeight, 'DF');

$pdf->SetXY($boxX + 10, $boxY + 5);
foreach ($detailRows as [$label, $value]) {
    $pdf->SetX($boxX + 10);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(45, 10, cleanText($label), 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(125, 10, $value, 0, 1);
}

// =====================
// CATEGORY DETAILS
// =====================
if (!empty($evaluationDetails)) {
    $pdf->Ln(12);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 9, '  Category Breakdown', 0, 1, 'L', true);

    $categoryWidth = 75;
    $ratingWidth = 35;
    $responsesWidth = 32;
    $weightWidth = 38;
    $tableX = 15;

    $pdf->SetX($tableX);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(30, 64, 175);
    $pdf->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);
    $pdf->Cell($categoryWidth, 8, 'Category', 1, 0, 'C', true);
    $pdf->Cell($ratingWidth, 8, 'Average', 1, 0, 'C', true);
    $pdf->Cell($responsesWidth, 8, 'Responses', 1, 0, 'C', true);
    $pdf->Cell($weightWidth, 8, 'Weight', 1, 1, 'C', true);

    $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
    $pdf->SetFont('Arial', '', 9);

    foreach ($evaluationDetails as $detail) {
        $category = cleanText($detail['category'] ?? 'N/A');
        $rating = cleanText($detail['average_score'] ?? $detail['overall_rating'] ?? '0.00');
        $responses = cleanText((string)($detail['responses'] ?? '0'));
        $weight = number_format((float)($detail['normalised_weight'] ?? 0), 2) . '%';

        $pdf->SetX($tableX);
        $pdf->Cell($categoryWidth, 9, $category, 1, 0, 'L');
        $pdf->Cell($ratingWidth, 9, $rating . ' / 5.00', 1, 0, 'C');
        $pdf->Cell($responsesWidth, 9, $responses, 1, 0, 'C');
        $pdf->Cell($weightWidth, 9, cleanText($weight), 1, 1, 'C');
    }
} elseif ($isAdminRequest) {
    $pdf->Ln(12);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 10, 'No evaluation details available', 0, 1, 'C');
}

if ($isAdminRequest) {
    $feedbackText = cleanText($allFeedback);
} else {
    $feedbackText = cleanText($feedback);
}
if ($feedbackText === '') {
    $feedbackText = 'No feedback available';
}

$pdf->Ln(10);

$pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, '  Feedback Comments', 0, 1, 'L', true);

$pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
$pdf->SetFont('Arial', '', 11);
$pdf->SetXY(15, $pdf->GetY() + 3);
$pdf->MultiCell(180, 6, $feedbackText, 0, 'L');

// =====================
// OUTPUT PDF
// =====================
while (ob_get_level() > 0) {
    ob_end_clean();
}
header_remove();
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Faculty_Evaluation_Report_' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $facultyName) . '.pdf"');
header('Cache-Control: private, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$output = $pdf->Output('S');
if ($output === false || $output === '') {
    http_response_code(500);
    exit('Failed to generate PDF stream.');
}

echo $output;
exit;

<?php
include_once 'session_config.php';
session_start();
include 'connect.php';
require_once 'evaluation_period_helper.php';

if (isset($_SESSION['role']) && $_SESSION['role'] === 'faculty' && isEvaluationOngoing($conn)) {
    http_response_code(403);
    exit('Evaluation results are unavailable while the evaluation process is still ongoing.');
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (
    !isset($data['facultyName']) ||
    !isset($data['facultyId'])
) {
    http_response_code(400);
    exit('Missing required data');
}

// Load FPDF
require('fpdf.php');

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
$subjectLabel = cleanText($data['subjectLabel'] ?? '');
$classLabel = cleanText($data['classLabel'] ?? '');
$subjectContext = trim($subjectLabel . ($classLabel !== '' ? " ({$classLabel})" : ''));

// Check if this is admin request (empty feedback indicates admin)
$isAdminRequest = (empty($data['feedback']) || $data['feedback'] === '');

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
$boxHeight = $subjectContext !== '' ? 55 : 45;
$boxY = 60;

$pdf->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);
$pdf->SetFillColor(248, 248, 248); // Light background
$pdf->Rect($boxX, $boxY, $boxWidth, $boxHeight, 'DF');

// Position content inside box
$pdf->SetXY($boxX + 10, $boxY + 5);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(60, 10, 'Name:', 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, $facultyName, 0, 1);

$pdf->SetX($boxX + 10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(60, 10, 'Faculty ID:', 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, $facultyIdDisplay, 0, 1);

$pdf->SetX($boxX + 10);
$pdf->SetFont('Arial', 'B', 12);
if ($subjectContext !== '') {
    $pdf->Cell(60, 10, 'Subject / Class:', 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, $subjectContext, 0, 1);
    $pdf->SetX($boxX + 10);
    $pdf->SetFont('Arial', 'B', 12);
}

$pdf->SetX($boxX + 10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(60, 10, 'Overall Ratings:', 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, $overallRating . ' / 5.00', 0, 1);

$pdf->SetX($boxX + 10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(60, 10, 'Total Student Responses:', 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, $totalResponses, 0, 1);

// =====================
// EVALUATION DETAILS TABLE (admin request)
// =====================
if ($isAdminRequest) {
    $pdf->Ln(12);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 9, '  Evaluation Details', 0, 1, 'L', true);

    $categoryWidth = 65;
    $ratingWidth = 35;
    $feedbackWidth = 90;
    $tableX = 10;

    $pdf->SetX($tableX);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(30, 64, 175);
    $pdf->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);
    $pdf->Cell($categoryWidth, 8, 'Category', 1, 0, 'C', true);
    $pdf->Cell($ratingWidth, 8, 'Overall Rating', 1, 0, 'C', true);
    $pdf->Cell($feedbackWidth, 8, 'All Feedback', 1, 1, 'C', true);

    $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
    $pdf->SetFont('Arial', '', 9);

    if (empty($evaluationDetails)) {
        $pdf->SetX($tableX);
        $pdf->Cell($categoryWidth + $ratingWidth + $feedbackWidth, 10, 'No evaluation details available', 1, 1, 'C');
    } else {
        foreach ($evaluationDetails as $index => $detail) {
            $category = cleanText($detail['category'] ?? 'N/A');
            $rating = cleanText($detail['average_score'] ?? $detail['overall_rating'] ?? '0.00');
            $feedbackText = $index === 0 ? cleanText($detail['all_feedback'] ?? $allFeedback) : '';
            $feedbackLines = $feedbackText !== ''
                ? explode("\n", wordwrap($feedbackText, 52, "\n", true))
                : [''];
            $feedbackChunks = array_chunk($feedbackLines, 8);

            foreach ($feedbackChunks as $chunkIndex => $chunkLines) {
                $chunkText = implode("\n", $chunkLines);
                $lineCount = max(1, count($chunkLines));
                $rowHeight = max(10, $lineCount * 5 + 4);

                if ($pdf->GetY() + $rowHeight > 275) {
                    $pdf->AddPage();
                }

                $x = $tableX;
                $y = $pdf->GetY();

                $pdf->Rect($x, $y, $categoryWidth, $rowHeight);
                $pdf->Rect($x + $categoryWidth, $y, $ratingWidth, $rowHeight);
                $pdf->Rect($x + $categoryWidth + $ratingWidth, $y, $feedbackWidth, $rowHeight);

                $pdf->SetXY($x + 2, $y + 2);
                $pdf->MultiCell($categoryWidth - 4, 5, $chunkIndex === 0 ? $category : '', 0, 'L');

                $pdf->SetXY($x + $categoryWidth, $y + 2);
                $pdf->Cell($ratingWidth, 5, $chunkIndex === 0 ? $rating . ' / 5.00' : '', 0, 0, 'C');

                $pdf->SetXY($x + $categoryWidth + $ratingWidth + 2, $y + 2);
                $pdf->MultiCell($feedbackWidth - 4, 5, $chunkText ?: ($index === 0 && $chunkIndex === 0 ? 'No feedback available' : ''), 0, 'L');

                $pdf->SetY($y + $rowHeight);
            }
        }
    }
}

// =====================
// FEEDBACK SECTION (only for instructor requests)
// =====================
if (!$isAdminRequest) {
    $pdf->Ln(10);

    // Feedback header with background
    $pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, '  Feedback Comments', 0, 1, 'L', true);

    // Reset for feedback content
    $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
    $pdf->SetFont('Arial', '', 11);

    // Feedback box with border
    $pdf->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect(10, $pdf->GetY(), 190, 40, 'DF');

    $pdf->MultiCell(
        0,
        6,
        $feedback,
        0
    );
}

// =====================
// OUTPUT PDF
// =====================
// Set proper headers for blob display
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . 'Faculty_Evaluation_Report_' . str_replace(' ', '_', $facultyName) . '.pdf' . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Output PDF as string for blob creation
echo $pdf->Output('S');
exit;
?>

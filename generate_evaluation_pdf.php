<?php
include 'connect.php';

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
$boxHeight = 45; // 4 rows * 10 + 5 padding
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
$pdf->Cell(60, 10, 'Overall Ratings:', 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, $overallRating . ' / 5.00', 0, 1);

$pdf->SetX($boxX + 10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(60, 10, 'Total Student Responses:', 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, $totalResponses, 0, 1);

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

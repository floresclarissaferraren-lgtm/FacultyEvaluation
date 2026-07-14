<?php
/**
 * generate_per_subject_report_pdf.php
 *
 * Generates a PDF for the per-subject faculty evaluation report.
 * Accepts POST JSON with the same structure returned by
 * get_faculty_per_subject_report.php.
 */
include 'connect.php';
require('fpdf.php');
define('FPDF_FONTPATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'font' . DIRECTORY_SEPARATOR);
date_default_timezone_set('Asia/Manila');

function ct($text) {
    return iconv('UTF-8', 'windows-1252//TRANSLIT', (string)$text);
}

function ratingLabel(float $score): string {
    if ($score <= 0)   return 'N/A';
    if ($score >= 4.5) return 'Outstanding';
    if ($score >= 3.5) return 'Very Good';
    if ($score >= 2.5) return 'Good';
    if ($score >= 1.5) return 'Fair';
    return 'Poor';
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    exit('Invalid input');
}

$facultyName = ct($data['facultyName'] ?? 'N/A');
$facultyId   = ct($data['facultyId']   ?? 'N/A');
$departments = $data['departments']    ?? [];
$overall     = $data['overall']        ?? [];

// Resolve display faculty_id (FC-XXXX) if numeric was passed
if (is_numeric($data['facultyId'] ?? '')) {
    $stmt = $conn->prepare("SELECT faculty_id FROM add_faculties WHERE id = ?");
    $stmt->bind_param("i", intval($data['facultyId']));
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($r) $facultyId = ct($r['faculty_id']);
}

/* ── PDF setup ──────────────────────────────────────────────────────────── */
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

$BLUE  = [70, 130, 180];
$DARK  = [50, 50, 50];
$LIGHT = [248, 249, 250];
$GRAY  = [200, 200, 200];
$WHITE = [255, 255, 255];
$NAVY  = [30, 64, 175];

/* helper: draw a section header bar */
function sectionBar(FPDF $pdf, string $text, array $color): void {
    $pdf->SetFillColor($color[0], $color[1], $color[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 8, '  ' . ct($text), 0, 1, 'L', true);
    $pdf->SetTextColor(50, 50, 50);
}

/* ── PAGE HEADER ─────────────────────────────────────────────────────────── */
$pdf->SetFillColor($BLUE[0], $BLUE[1], $BLUE[2]);
$pdf->Rect(10, 10, 190, 32, 'F');
if (file_exists('logo.png')) $pdf->Image('logo.png', 14, 14, 22);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(40, 16);
$pdf->Cell(155, 9, 'Granby Colleges of Science and Technology', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetX(40);
$pdf->Cell(155, 8, 'Faculty Per-Subject Evaluation Report', 0, 1, 'C');
$pdf->Ln(18);
$pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);

/* ── FACULTY INFO BOX ──────────────────────────────────────────────────── */
$pdf->SetFillColor($LIGHT[0], $LIGHT[1], $LIGHT[2]);
$pdf->SetDrawColor($GRAY[0], $GRAY[1], $GRAY[2]);
$pdf->Rect(10, $pdf->GetY(), 190, 26, 'DF');
$pdf->SetXY(14, $pdf->GetY() + 4);
$pdf->SetFont('Arial', 'B', 11); $pdf->Cell(40, 7, 'Faculty Name:', 0, 0);
$pdf->SetFont('Arial', '',  11); $pdf->Cell(0,  7, $facultyName, 0, 1);
$pdf->SetX(14);
$pdf->SetFont('Arial', 'B', 11); $pdf->Cell(40, 7, 'Faculty ID:', 0, 0);
$pdf->SetFont('Arial', '',  11); $pdf->Cell(0,  7, $facultyId, 0, 1);
$pdf->Ln(6);

/* ── OVERALL RATING SUMMARY ──────────────────────────────────────────────── */
sectionBar($pdf, 'OVERALL FACULTY RATING  (All Subjects Combined)', $BLUE);
$pdf->SetFillColor($LIGHT[0], $LIGHT[1], $LIGHT[2]);
$pdf->Rect(10, $pdf->GetY(), 190, 22, 'DF');
$pdf->SetXY(14, $pdf->GetY() + 4);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(60, 7, 'Weighted Average:', 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(40, 7, ct(($overall['avg_rating'] ?? '0.00') . ' / 5.00'), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(40, 7, 'Score:', 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 7, ct(($overall['percentage'] ?? '0.00') . '%'), 0, 1);
$pdf->SetX(14);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(60, 7, 'Performance Status:', 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(40, 7, ct($overall['rating_label'] ?? 'N/A'), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(40, 7, 'Total Evaluations:', 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 7, ct(strval($overall['eval_count'] ?? 0)), 0, 1);
$pdf->Ln(5);

/* ── OVERALL CATEGORY BREAKDOWN ─────────────────────────────────────────── */
if (!empty($overall['category_totals'])) {
    sectionBar($pdf, 'Overall Category Ratings', $NAVY);
    // Table header
    $pdf->SetFillColor($NAVY[0], $NAVY[1], $NAVY[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(80, 7, 'Category', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Avg Rating', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Responses', 1, 0, 'C', true);
    $pdf->Cell(50, 7, 'Status', 1, 1, 'C', true);
    $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
    $pdf->SetFont('Arial', '', 9);
    foreach ($overall['category_totals'] as $cat) {
        $avg = floatval($cat['avg_rating'] ?? 0);
        $pdf->Cell(80, 6, ct($cat['category_name'] ?? ''), 1, 0, 'L');
        $pdf->Cell(30, 6, ct($cat['avg_rating'] ?? '0.00') . ' / 5.00', 1, 0, 'C');
        $pdf->Cell(30, 6, ct(strval($cat['responses'] ?? 0)), 1, 0, 'C');
        $pdf->Cell(50, 6, ct(ratingLabel($avg)), 1, 1, 'C');
    }
    $pdf->Ln(4);
}

/* ── PER-DEPARTMENT / PER-SUBJECT SECTIONS ──────────────────────────────── */
foreach ($departments as $dept) {
    $progName = ct($dept['program_name'] ?? 'Unknown Program');
    $progCode = ct($dept['program_code'] ?? '');
    $label    = $progCode ? "$progName ($progCode)" : $progName;

    // Check page space before starting a department block
    if ($pdf->GetY() > 230) $pdf->AddPage();

    // Department header
    $pdf->SetFillColor(30, 80, 140);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 9, '  ' . $label, 0, 1, 'L', true);
    $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);

    foreach ($dept['subjects'] ?? [] as $subj) {
        // Page break guard
        if ($pdf->GetY() > 240) $pdf->AddPage();

        $code  = ct($subj['subject_code'] ?? '');
        $desc  = ct($subj['subject_desc'] ?? '');
        $cls   = ct($subj['class_label']  ?? '');
        $title = $code . ($desc !== '' ? " – $desc" : '') . ($cls !== '' ? "  [$cls]" : '');

        // Subject sub-header
        $pdf->SetFillColor(220, 235, 255);
        $pdf->SetTextColor(20, 40, 100);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 7, '  ' . $title, 0, 1, 'L', true);
        $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);

        // Subject summary row
        $pdf->SetFillColor($LIGHT[0], $LIGHT[1], $LIGHT[2]);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(45, 6, 'Average Rating:', 1, 0, 'L', true);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(45, 6, ct(($subj['avg_rating'] ?? '0.00') . ' / 5.00'), 1, 0, 'C');
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(35, 6, 'Evaluations:', 1, 0, 'L', true);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(30, 6, ct(strval($subj['eval_count'] ?? 0)), 1, 0, 'C');
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(15, 6, 'Status:', 1, 0, 'L', true);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 6, ct($subj['rating_label'] ?? 'N/A'), 1, 1, 'C');

        // Period
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(0, 5, ct('Evaluation period: ' . ($subj['period'] ?? 'N/A')), 0, 1, 'L');
        $pdf->Ln(1);

        // Category breakdown table
        if (!empty($subj['category_totals'])) {
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor($BLUE[0], $BLUE[1], $BLUE[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(75, 6, 'Category', 1, 0, 'C', true);
            $pdf->Cell(28, 6, 'Avg Rating', 1, 0, 'C', true);
            $pdf->Cell(28, 6, 'Responses', 1, 0, 'C', true);
            $pdf->Cell(59, 6, 'Status', 1, 1, 'C', true);
            $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
            $pdf->SetFont('Arial', '', 8);
            foreach ($subj['category_totals'] as $cat) {
                $cavg = floatval($cat['avg_rating'] ?? 0);
                $pdf->Cell(75, 5, ct($cat['category_name'] ?? ''), 1, 0, 'L');
                $pdf->Cell(28, 5, ct($cat['avg_rating'] ?? '0.00') . ' / 5', 1, 0, 'C');
                $pdf->Cell(28, 5, ct(strval($cat['responses'] ?? 0)), 1, 0, 'C');
                $pdf->Cell(59, 5, ct(ratingLabel($cavg)), 1, 1, 'C');
            }
        }
        $pdf->Ln(2);

        // Student comments (max 5 per subject to avoid overflow)
        $comments = $subj['comments'] ?? [];
        if (!empty($comments)) {
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(240, 245, 255);
            $pdf->SetTextColor(30, 50, 100);
            $pdf->Cell(0, 5, '  Student Comments', 0, 1, 'L', true);
            $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
            $pdf->SetFont('Arial', '', 8);
            $shown = array_slice($comments, 0, 5);
            foreach ($shown as $i => $comment) {
                if ($pdf->GetY() > 260) $pdf->AddPage();
                $num = $i + 1;
                $pdf->MultiCell(0, 4, ct("$num. $comment"), 0, 'L');
                $pdf->Ln(1);
            }
            if (count($comments) > 5) {
                $pdf->SetFont('Arial', 'I', 7);
                $pdf->Cell(0, 4, ct('... and ' . (count($comments) - 5) . ' more comment(s).'), 0, 1, 'L');
            }
        }
        $pdf->Ln(3);
    }
    $pdf->Ln(3);
}

/* ── FOOTER ─────────────────────────────────────────────────────────────── */
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(0, 5, ct('Generated on: ' . date('F j, Y  h:i A')), 0, 1, 'R');

/* ── OUTPUT ─────────────────────────────────────────────────────────────── */
$filename = 'Per_Subject_Report_' . str_replace(' ', '_', $facultyName) . '_' . date('Ymd') . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $pdf->Output('S');
exit;
?>

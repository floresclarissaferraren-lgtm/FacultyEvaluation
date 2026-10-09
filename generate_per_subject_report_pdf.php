<?php

include_once 'session_config.php';
session_start();
include 'connect.php';
require_once 'evaluation_period_helper.php';
if (!class_exists('FPDF', false)) {
    require_once __DIR__ . '/fpdf.php';
}
define('FPDF_FONTPATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'font' . DIRECTORY_SEPARATOR);
date_default_timezone_set('Asia/Manila');

if (isset($_SESSION['role']) && $_SESSION['role'] === 'faculty' && isEvaluationOngoing($conn)) {
    http_response_code(403);
    exit('Evaluation results are unavailable while the evaluation process is still ongoing.');
}

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

class FacultyPerSubjectReportPDF extends FPDF {
    public function Footer(): void {
        $this->SetY(-12);
        $this->SetDrawColor(210, 220, 232);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->SetY(-10);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(120, 130, 145);
        $this->Cell(130, 6, ct('Generated ' . date('M j, Y  h:i A')), 0, 0, 'L');
        $this->Cell(60, 6, ct('Page ' . $this->PageNo()), 0, 0, 'R');
    }
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data) || empty($data)) {
    $data = $_GET;
}

if (!isset($data['facultyName']) || !isset($data['facultyId']) || trim((string)$data['facultyName']) === '' || trim((string)$data['facultyId']) === '') {
    http_response_code(400);
    exit('Missing required data');
}

$facultyName = ct($data['facultyName'] ?? 'N/A');
$facultyId   = ct($data['facultyId']   ?? 'N/A');
$departments = is_array($data['departments'] ?? null) ? $data['departments'] : [];
$period = is_array($data['period'] ?? null) ? $data['period'] : [];
$academicYear = ct($period['academic_year'] ?? 'Not specified');
$semester = ct($period['semester'] ?? 'Not specified');
$periodDates = !empty($period['start_date']) && !empty($period['end_date'])
    ? ct(date('F j, Y', strtotime($period['start_date'])) . ' - ' . date('F j, Y', strtotime($period['end_date'])))
    : ct('Not specified');

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
$pdf = new FacultyPerSubjectReportPDF('P', 'mm', 'A4');
$pdf->SetAutoPageBreak(true, 17);
$pdf->AddPage();

$BLUE  = [11, 126, 196];
$NAVY  = [30, 64, 120];
$DARK  = [50, 50, 50];
$LIGHT = [245, 249, 255];
$GRAY  = [215, 225, 238];
$WHITE = [255, 255, 255];

/* ── PAGE HEADER ─────────────────────────────────────────────────────────── */
$pdf->SetFillColor($BLUE[0], $BLUE[1], $BLUE[2]);
$pdf->Rect(10, 10, 190, 34, 'F');
if (file_exists(__DIR__ . '/assets/images/logo.png')) $pdf->Image(__DIR__ . '/assets/images/logo.png', 15, 15, 23);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 15);
$pdf->SetXY(42, 16);
$pdf->Cell(153, 9, ct('Granby Colleges of Science and Technology'), 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetX(42);
$pdf->Cell(153, 8, ct('Faculty Per-Subject Evaluation Report'), 0, 1, 'C');
$pdf->Ln(20);
$pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);

/* ── FACULTY INFO BOX ──────────────────────────────────────────────────── */
$pdf->SetFillColor($LIGHT[0], $LIGHT[1], $LIGHT[2]);
$pdf->SetDrawColor($GRAY[0], $GRAY[1], $GRAY[2]);
$infoY = $pdf->GetY();
$pdf->Rect(10, $infoY, 190, 43, 'DF');
$pdf->SetXY(14, $pdf->GetY() + 4);
$pdf->SetFont('Arial', 'B', 11); $pdf->Cell(40, 7, 'Faculty Name:', 0, 0);
$pdf->SetFont('Arial', '',  11); $pdf->Cell(0,  7, $facultyName, 0, 1);
$pdf->SetX(14);
$pdf->SetFont('Arial', 'B', 11); $pdf->Cell(40, 7, 'Faculty ID:', 0, 0);
$pdf->SetFont('Arial', '',  11); $pdf->Cell(0,  7, $facultyId, 0, 1);
$pdf->SetX(14);
$pdf->SetFont('Arial', 'B', 11); $pdf->Cell(40, 7, 'Academic Year:', 0, 0);
$pdf->SetFont('Arial', '',  11); $pdf->Cell(0,  7, $academicYear, 0, 1);
$pdf->SetX(14);
$pdf->SetFont('Arial', 'B', 11); $pdf->Cell(40, 7, 'Semester:', 0, 0);
$pdf->SetFont('Arial', '',  11); $pdf->Cell(0,  7, $semester, 0, 1);
$pdf->SetX(14);
$pdf->SetFont('Arial', 'B', 11); $pdf->Cell(40, 7, 'Evaluation Dates:', 0, 0);
$pdf->SetFont('Arial', '',  11); $pdf->Cell(0,  7, $periodDates, 0, 1);
$pdf->SetY($infoY + 48);

/* ── PER-DEPARTMENT / PER-SUBJECT SECTIONS ──────────────────────────────── */
foreach ($departments as $dept) {
    $progName = ct($dept['program_name'] ?? 'Unknown Program');
    $progCode = ct($dept['program_code'] ?? '');
    $label    = $progCode ? "$progName ($progCode)" : $progName;
    $subjects = is_array($dept['subjects'] ?? null) ? $dept['subjects'] : [];

    // Check page space before starting a department block
    if ($pdf->GetY() > 250) $pdf->AddPage();

    // Department header
    $pdf->SetFillColor($NAVY[0], $NAVY[1], $NAVY[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 8, '  ' . $label . '  |  ' . count($subjects) . (count($subjects) === 1 ? ' subject' : ' subjects'), 0, 1, 'L', true);
    $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);

    foreach ($subjects as $subj) {
        // Page break guard
        if ($pdf->GetY() > 235) $pdf->AddPage();

        $code  = ct($subj['subject_code'] ?? '');
        $desc  = ct($subj['subject_desc'] ?? '');
        $title = $code . ($desc !== '' ? " - $desc" : '');

        // Subject sub-header
        $pdf->SetFillColor(220, 235, 255);
        $pdf->SetTextColor(20, 40, 100);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->MultiCell(0, 7, '  ' . $title, 0, 'L', true);
        $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);

        $avg = (float)($subj['avg_rating'] ?? 0);
        $scorePercent = max(0, min(100, (float)($subj['percentage'] ?? 0)));
        $evaluationCount = max(0, (int)($subj['eval_count'] ?? 0));
        $summaryY = $pdf->GetY() + 3;
        $pdf->SetFillColor($LIGHT[0], $LIGHT[1], $LIGHT[2]);
        $pdf->SetDrawColor($GRAY[0], $GRAY[1], $GRAY[2]);
        $pdf->Rect(10, $summaryY, 190, 24, 'DF');
        $summaryItems = [
            ['AVERAGE RATING', number_format($avg, 2) . ' / 5.00'],
            ['PERFORMANCE SCORE', number_format($scorePercent, 1) . '%'],
            ['EVALUATIONS', (string)$evaluationCount],
            ['STATUS', $evaluationCount > 0 ? ($subj['rating_label'] ?? ratingLabel($avg)) : 'No Responses'],
        ];
        $summaryWidth = 47.5;
        foreach ($summaryItems as $index => [$label, $value]) {
            $x = 10 + ($index * $summaryWidth);
            if ($index > 0) {
                $pdf->SetDrawColor($GRAY[0], $GRAY[1], $GRAY[2]);
                $pdf->Line($x, $summaryY + 4, $x, $summaryY + 20);
            }
            $pdf->SetXY($x + 3, $summaryY + 4);
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetTextColor(95, 110, 130);
            $pdf->Cell($summaryWidth - 6, 4, ct($label), 0, 1, 'L');
            $pdf->SetX($x + 3);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetTextColor($NAVY[0], $NAVY[1], $NAVY[2]);
            $pdf->Cell($summaryWidth - 6, 7, ct($value), 0, 1, 'L');
        }
        $pdf->SetY($summaryY + 27);
        $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 5, ct('Evaluation period: ' . ($subj['period'] ?? 'N/A')), 0, 1, 'L');
        $pdf->Ln(2);

        // Category breakdown table
        if (!empty($subj['category_totals'])) {
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor($NAVY[0], $NAVY[1], $NAVY[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(57, 7, 'Category', 1, 0, 'L', true);
            $pdf->Cell(23, 7, 'Weight', 1, 0, 'C', true);
            $pdf->Cell(24, 7, 'Score', 1, 0, 'C', true);
            $pdf->Cell(28, 7, 'Average', 1, 0, 'C', true);
            $pdf->Cell(27, 7, 'Responses', 1, 0, 'C', true);
            $pdf->Cell(30, 7, 'Status', 1, 1, 'C', true);
            $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
            $pdf->SetFont('Arial', '', 8);
            foreach (array_values($subj['category_totals']) as $rowIndex => $cat) {
                $cavg = floatval($cat['avg_rating'] ?? 0);
                $catResponses = max(0, (int)($cat['responses'] ?? 0));
                $catWeight = max(0, (float)($cat['normalised_weight'] ?? 0));
                $catPercent = $catResponses > 0 ? max(0, min(100, ($cavg / 5) * 100)) : 0;
                $pdf->SetFillColor(...($rowIndex % 2 === 0 ? [255, 255, 255] : [247, 250, 255]));
                $pdf->Cell(57, 7, ct($cat['category_name'] ?? ''), 1, 0, 'L', true);
                $pdf->Cell(23, 7, ct(number_format($catWeight, 1) . '%'), 1, 0, 'C', true);
                $pdf->Cell(24, 7, ct(number_format($catPercent, 0) . '%'), 1, 0, 'C', true);
                $pdf->Cell(28, 7, $catResponses > 0 ? ct(number_format($cavg, 2) . ' / 5') : 'N/A', 1, 0, 'C', true);
                $pdf->Cell(27, 7, ct((string)$catResponses), 1, 0, 'C', true);
                $pdf->Cell(30, 7, ct($catResponses > 0 ? ratingLabel($cavg) : 'N/A'), 1, 1, 'C', true);
            }
        } else {
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->SetTextColor(110, 120, 135);
            $pdf->Cell(0, 7, ct('No category ratings available for this subject.'), 0, 1, 'L');
        }
        $pdf->Ln(2);

        // Student comments (max 5 per subject to avoid overflow)
        $comments = is_array($subj['comments'] ?? null) ? $subj['comments'] : [];
        if (!empty($comments)) {
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(235, 243, 255);
            $pdf->SetTextColor($NAVY[0], $NAVY[1], $NAVY[2]);
            $pdf->Cell(0, 7, '  Student Comments  (' . count($comments) . ')', 0, 1, 'L', true);
            $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
            $pdf->SetFont('Arial', '', 8);
            $shown = array_slice($comments, 0, 5);
            foreach ($shown as $i => $comment) {
                if ($pdf->GetY() > 265) $pdf->AddPage();
                $num = $i + 1;
                $pdf->SetFillColor(250, 252, 255);
                $pdf->SetDrawColor($GRAY[0], $GRAY[1], $GRAY[2]);
                $commentText = $num . '. ' . trim((string)$comment);
                $pdf->SetX(13);
                $pdf->MultiCell(184, 5, ct($commentText), 1, 'L', true);
            }
            if (count($comments) > 5) {
                $pdf->SetFont('Arial', '', 7);
                $pdf->SetTextColor(110, 120, 135);
                $pdf->Cell(0, 5, ct('Showing 5 of ' . count($comments) . ' comments.'), 0, 1, 'L');
            }
        } else {
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->SetTextColor(110, 120, 135);
            $pdf->Cell(0, 7, ct('No comments were submitted for this subject.'), 0, 1, 'L');
        }
        $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
        $pdf->Ln(3);
    }
    $pdf->Ln(3);
}

/* ── OUTPUT ─────────────────────────────────────────────────────────────── */
ob_clean();
$filename = 'Per_Subject_Report_' . str_replace(' ', '_', $facultyName) . '_' . date('Ymd') . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
echo $pdf->Output('S');
exit;

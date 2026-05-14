<?php
include 'connect.php';
require('fpdf.php');
date_default_timezone_set('Asia/Manila');

$mode = $_GET['mode'] ?? 'view'; // view | download
$search = trim($_GET['search'] ?? '');

function getStatusLabel($score) {
    if ($score == 0) return 'N/A';
    if ($score >= 4.5) return 'Outstanding';
    if ($score >= 3.5) return 'Very Good';
    if ($score >= 2.5) return 'Good';
    if ($score >= 1.5) return 'Fair';
    return 'Poor';
}

$sql = "SELECT 
            f.id,
            f.faculty_id,
            f.firstname,
            f.lastname,
            f.suffix,
            COUNT(e.id) AS total_responses,
            AVG(e.overall_rating) AS average_score
        FROM add_faculties f
        LEFT JOIN evaluations e ON f.id = e.faculty_id";

$params = [];
$types = '';
if ($search !== '') {
    $sql .= " WHERE CONCAT(f.firstname, ' ', f.lastname, ' ', COALESCE(f.suffix, ''), ' ', f.faculty_id) LIKE ?";
    $params[] = '%' . $search . '%';
    $types .= 's';
}

$sql .= " GROUP BY f.id, f.faculty_id, f.firstname, f.lastname, f.suffix
          ORDER BY average_score DESC";

$stmt = $conn->prepare($sql);
if ($stmt && !empty($params)) {
    $refs = [];
    foreach ($params as $k => $v) {
        $refs[$k] = &$params[$k];
    }
    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

$rows = [];
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $avg = $r['average_score'] ? round($r['average_score'], 2) : 0;
        $rows[] = [
            'name' => trim($r['firstname'] . ' ' . $r['lastname'] . ' ' . $r['suffix']),
            'rating' => number_format($avg, 2),
            'responses' => intval($r['total_responses']),
            'status' => getStatusLabel($avg)
        ];
    }
    $stmt->close();
} else {
    http_response_code(500);
    exit('Unable to generate report: query preparation failed.');
}

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 15);

$headerColor = [70, 130, 180]; // same blue tone as existing PDF
$textColor = [50, 50, 50];

$pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
$pdf->Rect(10, 10, 190, 30, 'F');

if (file_exists('logo.png')) {
    $pdf->Image('logo.png', 15, 14, 20);
}

$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 18);
$pdf->SetXY(40, 16);
$pdf->Cell(150, 8, 'Granby Colleges', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetX(40);
$pdf->Cell(150, 8, 'Fcaulty Performance Summary Report', 0, 1, 'C');

$pdf->Ln(18);
$pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 7, 'Generated on: ' . date('Y-m-d h:i:s A'), 0, 1, 'R');
$pdf->Ln(2);

$pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(80, 9, 'Fcaulty Name', 1, 0, 'C', true);
$pdf->Cell(35, 9, 'Overall Rtaing', 1, 0, 'C', true);
$pdf->Cell(30, 9, 'Responses', 1, 0, 'C', true);
$pdf->Cell(45, 9, 'Status', 1, 1, 'C', true);

$pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
$pdf->SetFont('Arial', '', 10);

if (count($rows) === 0) {
    $pdf->Cell(190, 10, 'No evaluation data found', 1, 1, 'C');
} else {
    foreach ($rows as $row) {
        $pdf->Cell(80, 8, substr($row['name'], 0, 42), 1, 0, 'L');
        $pdf->Cell(35, 8, $row['rating'], 1, 0, 'C');
        $pdf->Cell(30, 8, $row['responses'], 1, 0, 'C');
        $pdf->Cell(45, 8, $row['status'], 1, 1, 'C');
    }
}

$filename = 'Faculty_Performance_Summary_Report_' . date('Ymd_His') . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: ' . ($mode === 'download' ? 'attachment' : 'inline') . '; filename="' . $filename . '"');
echo $pdf->Output('S');

$conn->close();
?>

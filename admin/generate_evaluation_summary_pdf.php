<?php
ob_start();
require_once __DIR__ . "/../shared/security.php";
requireRole("admin");
include __DIR__ . "/../shared/connect.php";
if (!class_exists("FPDF", false)) {
    require_once __DIR__ . "/../vendor/fpdf/fpdf.php";
}
require_once __DIR__ . "/../shared/weighted_score_helper.php";
require_once __DIR__ . "/../shared/evaluation_schema.php";
require_once __DIR__ . "/../shared/evaluation_privacy.php";
date_default_timezone_set("Asia/Manila");

$mode = $_GET["mode"] ?? "view"; // view | download
$search = trim($_GET["search"] ?? "");

function getStatusLabel($score)
{
    if ($score == 0) {
        return "N/A";
    }
    if ($score >= 4.5) {
        return "Outstanding";
    }
    if ($score >= 3.5) {
        return "Very Good";
    }
    if ($score >= 2.5) {
        return "Good";
    }
    if ($score >= 1.5) {
        return "Fair";
    }
    return "Poor";
}

ensureEvaluationsSchema($conn);

$sql = "SELECT 
            f.id,
            f.faculty_id,
            f.firstname,
            f.lastname,
            f.suffix,
            COUNT(e.id) AS total_responses,
            COUNT(DISTINCT e.student_id) AS privacy_respondents,
            AVG(e.overall_rating) AS average_score
        FROM evaluations e
        INNER JOIN add_faculties f ON f.id = e.faculty_id";

$params = [];
$types = "";
if ($search !== "") {
    $sql .=
        " WHERE CONCAT(f.firstname, ' ', f.lastname, ' ', COALESCE(f.suffix, ''), ' ', f.faculty_id) LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
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
    call_user_func_array([$stmt, "bind_param"], $refs);
}

$rows = [];
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $facultyId = intval($r["id"] ?? 0);
        $privacySuppressed =
            intval($r["total_responses"]) > 0 &&
            !evaluationMeetsPrivacyThreshold(intval($r["privacy_respondents"]));
        $avg =
            $facultyId > 0 && !$privacySuppressed
                ? calcWeightedScore($conn, $facultyId)
                : 0;
        $rows[] = [
            "name" => trim(
                $r["firstname"] . " " . $r["lastname"] . " " . $r["suffix"],
            ),
            "rating" => $privacySuppressed
                ? "Withheld"
                : number_format($avg, 2),
            "responses" => $privacySuppressed
                ? "Withheld"
                : intval($r["total_responses"]),
            "status" => $privacySuppressed ? "Protected" : getStatusLabel($avg),
        ];
    }
    $stmt->close();
} else {
    http_response_code(500);
    exit("Unable to generate report: query preparation failed.");
}

$pdf = new FPDF("P", "mm", "A4");
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 15);

$headerColor = [11, 126, 196];
$textColor = [50, 50, 50];

$pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
$pdf->Rect(10, 10, 190, 34, "F");

$logoPath = __DIR__ . "/../assets/images/logo.png";
if (is_file($logoPath)) {
    $pdf->Image($logoPath, 15, 15, 23);
}

$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont("Arial", "B", 15);
$pdf->SetXY(10, 16);
$pdf->Cell(190, 9, "Granby Colleges of Science and Technology", 0, 1, "C");
$pdf->SetFont("Arial", "B", 11);
$pdf->SetX(10);
$pdf->Cell(190, 8, "Faculty Performance Summary Report", 0, 1, "C");

$pdf->SetY(50);
$pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
$pdf->SetFont("Arial", "", 10);
$pdf->Cell(0, 7, "Generated on: " . date("Y-m-d h:i:s A"), 0, 1, "R");
$pdf->Ln(2);

$pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont("Arial", "B", 10);
$pdf->Cell(70, 9, "Faculty Name", 1, 0, "C", true);
$pdf->Cell(35, 9, "Overall Rating", 1, 0, "C", true);
$pdf->Cell(35, 9, "Responses", 1, 0, "C", true);
$pdf->Cell(50, 9, "Status", 1, 1, "C", true);

$pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
$pdf->SetFont("Arial", "", 10);

if (count($rows) === 0) {
    $pdf->Cell(190, 10, "No evaluation data found", 1, 1, "C");
} else {
    foreach ($rows as $index => $row) {
        $pdf->SetFillColor(
            ...$index % 2 === 0 ? [255, 255, 255] : [247, 250, 255],
        );
        $pdf->Cell(70, 8, substr($row["name"], 0, 35), 1, 0, "L", true);
        $pdf->Cell(35, 8, $row["rating"], 1, 0, "C", true);
        $pdf->Cell(35, 8, $row["responses"], 1, 0, "C", true);
        $pdf->Cell(50, 8, $row["status"], 1, 1, "C", true);
    }
}

ob_clean();
$filename = "Faculty_Performance_Summary_Report_" . date("Ymd_His") . ".pdf";
header("Content-Type: application/pdf");
header(
    "Content-Disposition: " .
        ($mode === "download" ? "attachment" : "inline") .
        '; filename="' .
        $filename .
        '"',
);
header("Cache-Control: private, max-age=0, must-revalidate");
header("Pragma: public");
echo $pdf->Output("S");

$conn->close();

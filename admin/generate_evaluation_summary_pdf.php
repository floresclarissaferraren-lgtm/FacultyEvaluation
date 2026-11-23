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
date_default_timezone_set("Asia/Manila");

$mode = $_GET["mode"] ?? "view"; // view | download
$search = trim($_GET["search"] ?? "");
$academicYear = trim((string) ($_GET["academic_year"] ?? ""));
$semester = trim((string) ($_GET["semester"] ?? ""));

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
            GROUP_CONCAT(DISTINCT ep.id ORDER BY ep.id) AS period_ids
        FROM evaluations e
        INNER JOIN add_faculties f ON f.id = e.faculty_id
        LEFT JOIN evaluation_periods ep
            ON TRIM(ep.ay) = TRIM(e.academic_year)
           AND LOWER(TRIM(ep.semester)) = LOWER(TRIM(e.semester))";

$params = [];
$types = "";
$where = [
    "ep.id IS NOT NULL",
    "ep.end_date IS NOT NULL",
    "(ep.end_date < CURDATE() OR ep.is_active = 0)",
];
if ($academicYear !== "") {
    $where[] = "TRIM(ep.ay) = ?";
    $params[] = $academicYear;
    $types .= "s";
}
if ($semester !== "") {
    $where[] = "LOWER(TRIM(ep.semester)) = LOWER(?)";
    $params[] = $semester;
    $types .= "s";
}
if ($search !== "") {
    $where[] =
        "CONCAT(f.firstname, ' ', f.lastname, ' ', COALESCE(f.suffix, ''), ' ', f.faculty_id) LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

$sql .=
    " WHERE " .
    implode(" AND ", $where) .
    " GROUP BY f.id, f.faculty_id, f.firstname, f.lastname, f.suffix
      ORDER BY f.lastname ASC, f.firstname ASC";

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
$subjectCountStmt = $conn->prepare("
    SELECT COUNT(DISTINCT CASE WHEN e.subject_id > 0 THEN e.subject_id END) AS subject_count
    FROM evaluations e
    WHERE e.faculty_id = ?
      AND EXISTS (
          SELECT 1
          FROM evaluation_periods ep
          WHERE ep.id = ?
            AND TRIM(ep.ay) = TRIM(e.academic_year)
            AND LOWER(TRIM(ep.semester)) = LOWER(TRIM(e.semester))
            AND ep.end_date IS NOT NULL
            AND (ep.end_date < CURDATE() OR ep.is_active = 0)
      )
");
if (!$subjectCountStmt) {
    http_response_code(500);
    exit("Unable to generate report: subject count query preparation failed.");
}
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $facultyId = intval($r["id"] ?? 0);
        $periodIds = array_values(
            array_filter(
                array_map(
                    "intval",
                    explode(",", (string) ($r["period_ids"] ?? "")),
                ),
                static fn($periodId) => $periodId > 0,
            ),
        );
        $weightedSum = 0.0;
        $subjectCount = 0;
        foreach ($periodIds as $periodId) {
            $periodScore = calcWeightedScore(
                $conn,
                $facultyId,
                null,
                null,
                $periodId,
            );
            $subjectCountStmt->bind_param("ii", $facultyId, $periodId);
            $subjectCountStmt->execute();
            $subjectCountResult = $subjectCountStmt->get_result();
            $subjectCountRow = $subjectCountResult->fetch_assoc();
            $subjectCountResult->free();
            $periodSubjectCount = intval(
                $subjectCountRow["subject_count"] ?? 0,
            );
            if ($periodScore <= 0 || $periodSubjectCount <= 0) {
                continue;
            }
            $weightedSum += $periodScore * $periodSubjectCount;
            $subjectCount += $periodSubjectCount;
        }
        $avg = $subjectCount > 0
            ? round($weightedSum / $subjectCount, 2)
            : 0.0;
        $rows[] = [
            "name" => trim(
                $r["firstname"] . " " . $r["lastname"] . " " . $r["suffix"],
            ),
            "rating" => number_format($avg, 2),
            "percentage" => number_format(($avg / 5) * 100, 1),
            "responses" => intval($r["total_responses"]),
            "status" => getStatusLabel($avg),
        ];
    }
    $stmt->close();
    $subjectCountStmt->close();
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
$pdf->Rect(10, 10, 190, 28, "F");

$logoPath = __DIR__ . "/../assets/images/granbylogo.png";
if (is_file($logoPath)) {
    $pdf->Image($logoPath, 15, 13, 19);
}

$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont("Arial", "B", 13);
$pdf->SetXY(10, 14);
$pdf->Cell(190, 8, "Granby Colleges of Science and Technology", 0, 1, "C");
$pdf->SetFont("Arial", "B", 10);
$pdf->SetXY(10, 23);
$pdf->Cell(190, 7, "Faculty Performance Summary Report", 0, 1, "C");

$pdf->SetY(43);
$pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
$pdf->SetFont("Arial", "", 10);
$pdf->Cell(0, 7, "Generated on: " . date("Y-m-d h:i:s A"), 0, 1, "R");
$pdf->Ln(2);

$pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont("Arial", "B", 10);
$pdf->Cell(60, 9, "Faculty Name", 1, 0, "C", true);
$pdf->Cell(35, 9, "Overall Rating", 1, 0, "C", true);
$pdf->Cell(25, 9, "Percent", 1, 0, "C", true);
$pdf->Cell(30, 9, "Responses", 1, 0, "C", true);
$pdf->Cell(40, 9, "Status", 1, 1, "C", true);

$pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
$pdf->SetFont("Arial", "", 10);

if (count($rows) === 0) {
    $pdf->Cell(190, 10, "No evaluation data found", 1, 1, "C");
} else {
    foreach ($rows as $index => $row) {
        $pdf->SetFillColor(
            ...$index % 2 === 0 ? [255, 255, 255] : [247, 250, 255],
        );
        $pdf->Cell(60, 8, substr($row["name"], 0, 30), 1, 0, "L", true);
        $pdf->Cell(35, 8, $row["rating"], 1, 0, "C", true);
        $pdf->Cell(25, 8, $row["percentage"] . "%", 1, 0, "C", true);
        $pdf->Cell(30, 8, $row["responses"], 1, 0, "C", true);
        $pdf->Cell(40, 8, $row["status"], 1, 1, "C", true);
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

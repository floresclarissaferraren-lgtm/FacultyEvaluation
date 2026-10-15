<?php
require_once __DIR__ . "/security.php";
requireRole("admin", "faculty");
include __DIR__ . "/connect.php";
require_once __DIR__ . "/evaluation_period_helper.php";

if (
    isset($_SESSION["role"]) &&
    $_SESSION["role"] === "faculty" &&
    isEvaluationOngoing($conn)
) {
    http_response_code(403);
    exit(
        "Evaluation results are unavailable while the evaluation process is still ongoing."
    );
}

// Get JSON data, with GET fallback for direct URL access/testing.
$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data) || empty($data)) {
    $data = $_GET;
}

// Validate required fields from either JSON or GET.
if (
    !isset($data["facultyName"]) ||
    !isset($data["facultyId"]) ||
    trim((string) $data["facultyName"]) === "" ||
    trim((string) $data["facultyId"]) === ""
) {
    http_response_code(400);
    exit("Missing required data");
}

// Load FPDF once
if (!class_exists("FPDF", false)) {
    require_once __DIR__ . "/../vendor/fpdf/fpdf.php";
}

// Fix UTF-8 text for FPDF
function cleanText($text)
{
    return iconv("UTF-8", "windows-1252//TRANSLIT", $text);
}

function ratingLabel(float $score): string
{
    if ($score <= 0) {
        return "No Responses";
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

// Safe values
$facultyName = cleanText($data["facultyName"]);
$facultyId = cleanText($data["facultyId"]);
$overallRating = cleanText($data["overallRating"] ?? "0.00");
$totalResponses = cleanText($data["totalResponses"] ?? "0");
$feedback = cleanText($data["feedback"] ?? "No feedback available");
$evaluationDetails = is_array($data["evaluationDetails"] ?? null)
    ? $data["evaluationDetails"]
    : [];
$allFeedback = cleanText(
    $data["allFeedback"] ?? ($data["feedback"] ?? "No feedback available"),
);
$evaluationPeriod = cleanText($data["evaluationPeriod"] ?? "");
$academicYear = cleanText($data["academicYear"] ?? "");
$semester = cleanText($data["semester"] ?? "");
$programFilter = cleanText($data["programFilter"] ?? "");
$subjectLabel = cleanText($data["subjectLabel"] ?? "");
$subjectContext = $subjectLabel;

$isFacultyReport = ($data["reportType"] ?? "") === "faculty";
$privacySuppressed =
    (string) ($_SESSION["role"] ?? "") !== "faculty" &&
    !empty($data["privacySuppressed"]);

if ((string) ($_SESSION["role"] ?? "") === "faculty") {
    if (!$isFacultyReport) {
        http_response_code(403);
        exit("Access denied.");
    }

    $requestedFacultyId = trim((string) $data["facultyId"]);
    if (ctype_digit($requestedFacultyId)) {
        $requestedFacultyNumericId = intval($requestedFacultyId);
    } else {
        $facultyLookup = $conn->prepare(
            "SELECT id FROM add_faculties WHERE faculty_id = ? LIMIT 1",
        );
        $facultyLookup->bind_param("s", $requestedFacultyId);
        $facultyLookup->execute();
        $facultyLookupRow = $facultyLookup->get_result()->fetch_assoc();
        $facultyLookup->close();
        $requestedFacultyNumericId = intval($facultyLookupRow["id"] ?? 0);
    }

    if ($requestedFacultyNumericId !== intval($_SESSION["id"] ?? 0)) {
        http_response_code(403);
        exit("Access denied.");
    }
}

if ($privacySuppressed) {
    $overallRating = "Withheld";
    $totalResponses = "Withheld";
    $feedback =
        "Feedback and results are withheld until at least 5 students have responded.";
    $allFeedback = $feedback;
    $evaluationDetails = [];
}

// Get faculty_id from add_faculties table
$facultyIdDisplay = $facultyId; // Default to original ID
$stmt = $conn->prepare("SELECT faculty_id FROM add_faculties WHERE id = ?");
$stmt->bind_param("i", $facultyId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $facultyIdDisplay = $row["faculty_id"];
}
$stmt->close();

// Create PDF
$pdf = new class ("P", "mm", "A4") extends FPDF {
    public function Footer(): void
    {
        $this->SetY(-12);
        $this->SetDrawColor(210, 220, 232);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->SetY(-10);
        $this->SetFont("Arial", "", 8);
        $this->SetTextColor(120, 130, 145);
        $this->Cell(
            130,
            6,
            cleanText("Generated " . date("M j, Y  h:i A")),
            0,
            0,
            "L",
        );
        $this->Cell(60, 6, cleanText("Page " . $this->PageNo()), 0, 0, "R");
    }
};
$pdf->SetAutoPageBreak(true, 17);
$pdf->AddPage();

$blue = [11, 126, 196];
$navy = [30, 64, 120];
$light = [245, 249, 255];
$border = [215, 225, 238];
$textColor = [50, 50, 50];

// Use one shared layout for the admin and faculty evaluation reports.
$pdf->SetFillColor($blue[0], $blue[1], $blue[2]);
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
$pdf->Cell(190, 8, "Faculty Evaluation Report", 0, 1, "C");
$pdf->SetY(64);

$pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);

$boxX = 10;
$boxWidth = 190;
$detailRows = [
    ["Faculty Name:", $facultyName],
    ["Faculty ID:", $facultyIdDisplay],
];
if ($academicYear !== "") {
    $detailRows[] = ["Academic Year:", $academicYear];
}
if ($semester !== "") {
    $detailRows[] = ["Semester:", $semester];
}
if ($evaluationPeriod !== "") {
    $detailRows[] = ["Evaluation Period:", $evaluationPeriod];
}
if ($subjectContext !== "") {
    $detailRows[] = ["Subject:", $subjectContext];
}
if ($programFilter !== "") {
    $detailRows[] = ["Program:", $programFilter];
}
$boxHeight = 8 + count($detailRows) * 7;
$boxY = $pdf->GetY();

$pdf->SetDrawColor($border[0], $border[1], $border[2]);
$pdf->SetFillColor($light[0], $light[1], $light[2]);
$pdf->Rect($boxX, $boxY, $boxWidth, $boxHeight, "DF");

$pdf->SetXY($boxX + 4, $boxY + 4);
foreach ($detailRows as [$label, $value]) {
    $pdf->SetX($boxX + 4);
    $pdf->SetFont("Arial", "B", 10);
    $pdf->Cell(40, 7, cleanText($label), 0, 0);
    $pdf->SetFont("Arial", "", 10);
    $pdf->Cell(142, 7, $value, 0, 1);
}

$pdf->SetY($boxY + $boxHeight + 4);

$score = is_numeric($data["overallRating"] ?? null)
    ? (float) $data["overallRating"]
    : 0.0;
$performancePercent = max(0, min(100, $score * 20));
$performanceStatus = $privacySuppressed
    ? "Withheld"
    : (string) ($data["overallStatus"] ??
        ($data["overall_status"] ?? ratingLabel($score)));
$evaluationCount = $privacySuppressed ? "Withheld" : $totalResponses;

$pdf->SetFillColor($navy[0], $navy[1], $navy[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont("Arial", "B", 10);
$pdf->Cell(190, 8, "  OVERALL FACULTY RATING", 0, 1, "L", true);

$summaryY = $pdf->GetY();
$summaryWidth = 47.5;
$pdf->SetFillColor($light[0], $light[1], $light[2]);
$pdf->SetDrawColor($border[0], $border[1], $border[2]);
$pdf->Rect(10, $summaryY, 190, 23, "DF");
$summaryItems = [
    [
        "OVERALL RATING",
        $privacySuppressed ? "Withheld" : cleanText($overallRating) . " / 5.00",
    ],
    [
        "PERFORMANCE SCORE",
        $privacySuppressed
            ? "Withheld"
            : number_format($performancePercent, 1) . "%",
    ],
    ["EVALUATIONS", $evaluationCount],
    ["STATUS", $performanceStatus],
];
foreach ($summaryItems as $index => [$label, $value]) {
    $x = 10 + $index * $summaryWidth;
    if ($index > 0) {
        $pdf->Line($x, $summaryY + 3, $x, $summaryY + 20);
    }
    $pdf->SetXY($x + 3, $summaryY + 4);
    $pdf->SetFont("Arial", "B", 7);
    $pdf->SetTextColor(95, 110, 130);
    $pdf->Cell($summaryWidth - 6, 4, $label, 0, 1, "L");
    $pdf->SetX($x + 3);
    $pdf->SetFont("Arial", "B", 10);
    $pdf->SetTextColor($navy[0], $navy[1], $navy[2]);
    $pdf->Cell($summaryWidth - 6, 7, cleanText((string) $value), 0, 1, "L");
}
$pdf->SetY($summaryY + 27);

$pdf->SetFillColor($navy[0], $navy[1], $navy[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont("Arial", "B", 10);
$pdf->Cell(190, 8, "  Overall Category Ratings", 0, 1, "L", true);

$pdf->SetFillColor($navy[0], $navy[1], $navy[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetDrawColor($border[0], $border[1], $border[2]);
$pdf->SetFont("Arial", "B", 8);
$categoryWidths = [53, 22, 22, 31, 27, 35];
foreach (
    ["Category", "Weight", "Score", "Average", "Responses", "Status"]
    as $index => $heading
) {
    $pdf->Cell(
        $categoryWidths[$index],
        7,
        $heading,
        1,
        $index === 5 ? 1 : 0,
        "C",
        true,
    );
}

$pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
$pdf->SetFont("Arial", "", 8);
if (empty($evaluationDetails)) {
    $pdf->Cell(190, 8, "No category evaluation data available.", 1, 1, "C");
} else {
    foreach ($evaluationDetails as $index => $detail) {
        $category = cleanText(
            $detail["category"] ?? ($detail["category_name"] ?? "N/A"),
        );
        $rawRating =
            $detail["average_score"] ??
            ($detail["overall_rating"] ?? ($detail["avg_rating"] ?? "0.00"));
        $ratingValue = is_numeric($rawRating) ? (float) $rawRating : 0.0;
        $rating = cleanText((string) $rawRating);
        $responses = cleanText(
            (string) ($detail["responses"] ??
                ($detail["total_responses"] ?? "0")),
        );
        $weight =
            number_format(
                (float) ($detail["normalised_weight"] ??
                    ($detail["weight"] ?? 0)),
                1,
            ) . "%";
        $scorePercent =
            number_format(max(0, min(100, $ratingValue * 20)), 0) . "%";
        $status = cleanText(
            $detail["rating"] ??
                ($detail["status"] ?? ratingLabel($ratingValue)),
        );
        $pdf->SetFillColor(
            ...$index % 2 === 0 ? [255, 255, 255] : [247, 250, 255],
        );
        $values = [
            $category,
            cleanText($weight),
            cleanText($scorePercent),
            $ratingValue > 0 ? $rating . " / 5" : "N/A",
            $responses,
            $status,
        ];
        foreach ($values as $column => $value) {
            $pdf->Cell(
                $categoryWidths[$column],
                7,
                $value,
                1,
                $column === 5 ? 1 : 0,
                $column === 0 ? "L" : "C",
                true,
            );
        }
    }
}

$feedbackText =
    trim((string) ($data["feedback"] ?? "")) !== "" ? $feedback : $allFeedback;
if ($feedbackText === "") {
    $feedbackText = "No feedback available";
}

$comments = array_values(
    array_filter(
        array_map("trim", preg_split("/\R{2,}/", $feedbackText) ?: []),
        static fn($comment) => $comment !== "",
    ),
);
if (empty($comments)) {
    $comments = [$feedbackText];
}

$pdf->Ln(4);
$pdf->SetFillColor(220, 235, 255);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetTextColor(20, 40, 100);
$pdf->SetFont("Arial", "B", 9);
$pdf->Cell(
    190,
    7,
    "  Student Comments  (" . count($comments) . ")",
    0,
    1,
    "L",
    true,
);

$pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
$pdf->SetFont("Arial", "", 8);
foreach ($comments as $index => $comment) {
    if ($pdf->GetY() > 265) {
        $pdf->AddPage();
    }
    $pdf->SetFillColor(250, 252, 255);
    $pdf->SetDrawColor($border[0], $border[1], $border[2]);
    $pdf->SetX(13);
    $pdf->MultiCell(
        184,
        5,
        cleanText($index + 1 . ". " . $comment),
        1,
        "L",
        true,
    );
}

// =====================
// OUTPUT PDF
// =====================
while (ob_get_level() > 0) {
    ob_end_clean();
}
header_remove();
header("Content-Type: application/pdf");
header(
    'Content-Disposition: attachment; filename="Faculty_Evaluation_Report_' .
        preg_replace("/[^A-Za-z0-9._-]+/", "_", $facultyName) .
        '.pdf"',
);
header("Cache-Control: private, no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$output = $pdf->Output("S");
if ($output === false || $output === "") {
    http_response_code(500);
    exit("Failed to generate PDF stream.");
}

echo $output;
exit();

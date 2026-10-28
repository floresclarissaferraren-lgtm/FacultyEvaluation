<?php
require_once __DIR__ . "/../shared/security.php";
requireRole("admin");
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include __DIR__ . "/../shared/connect.php";
require_once __DIR__ . "/../shared/weighted_score_helper.php";
require_once __DIR__ . "/../shared/evaluation_schema.php";

try {
    ensureEvaluationsSchema($conn);

    $academicYear = trim((string) ($_GET["academic_year"] ?? ""));
    $semester = trim((string) ($_GET["semester"] ?? ""));

    $where = [
        "ep.id IS NOT NULL",
        "ep.end_date IS NOT NULL",
        "(ep.end_date < CURDATE() OR ep.is_active = 0)",
    ];
    $params = [];
    $types = "";

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

    // Get one computed result per instructor per evaluation period.
    $query =
        "SELECT 
        f.id,
        f.faculty_id,
        f.firstname,
        f.lastname,
        f.suffix,
        f.email,
        COALESCE(ep.id, 0) AS resolved_period_id,
        ep.ay AS academic_year,
        ep.semester,
        COUNT(DISTINCT e.student_id) as total_responses,
        MAX(e.created_at) as last_evaluation
    FROM evaluations e
    INNER JOIN add_faculties f ON f.id = e.faculty_id
    LEFT JOIN add_subjects s ON s.id = e.subject_id
    LEFT JOIN add_classes ac ON ac.id = e.class_id
    LEFT JOIN evaluation_periods ep
        ON TRIM(ep.ay) = TRIM(e.academic_year)
       AND LOWER(TRIM(ep.semester)) = LOWER(TRIM(e.semester))
    " .
        (!empty($where) ? " WHERE " . implode(" AND ", $where) : "") .
        "
    GROUP BY f.id, f.faculty_id, f.firstname, f.lastname, f.suffix, f.email,
        resolved_period_id, ep.ay, ep.semester
    ORDER BY ep.ay DESC, ep.semester ASC, f.lastname ASC, f.firstname ASC";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Unable to prepare evaluation query.");
    }
    if ($types !== "") {
        bindDynamicParams($stmt, $types, $params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $evaluations = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $fid = intval($row["id"]);
            $period_id = intval($row["resolved_period_id"] ?? 0);
            $total = intval($row["total_responses"]);
            $average_score =
                $total > 0
                    ? calcWeightedScore(
                        $conn,
                        $fid,
                        null,
                        null,
                        $period_id ?: null,
                    )
                    : 0.0;
            $rating = getRatingLabel($average_score);
            $periodLabel = trim(
                ($row["academic_year"] ?? "") .
                    (($row["semester"] ?? "") !== ""
                        ? " - " . $row["semester"]
                        : ""),
            );

            $evaluations[] = [
                "id" => $fid,
                "subject_id" => 0,
                "class_id" => 0,
                "period_id" => $period_id,
                "faculty_id" => $row["faculty_id"],
                "name" => trim(
                    $row["firstname"] .
                        " " .
                        $row["lastname"] .
                        " " .
                        $row["suffix"],
                ),
                "email" => $row["email"],
                "subject_label" => "",
                "class_label" => "",
                "academic_year" => $row["academic_year"] ?? "",
                "semester" => $row["semester"] ?? "",
                "period_label" =>
                    $periodLabel !== "" ? $periodLabel : "Unassigned Period",
                "average_score" => $average_score,
                "percentage_score" =>
                    $total > 0 ? round(($average_score / 5) * 100, 2) : 0,
                "rating" => $rating,
                "rating_class" => getRatingClass($average_score),
                "total_responses" => $total,
            ];
        }
    }
    $stmt->close();

    // Sort by weighted score descending
    usort(
        $evaluations,
        fn($a, $b) => $b["average_score"] <=> $a["average_score"],
    );

    echo json_encode([
        "success" => true,
        "data" => $evaluations,
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error fetching evaluations: " . $e->getMessage(),
    ]);
}

function getRatingLabel($score)
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

function getRatingClass($score)
{
    if ($score == 0) {
        return "na";
    }
    if ($score >= 4.5) {
        return "outstanding";
    }
    if ($score >= 3.5) {
        return "very-good";
    }
    if ($score >= 2.5) {
        return "good";
    }
    if ($score >= 1.5) {
        return "fair";
    }
    return "poor";
}

$conn->close();
?>

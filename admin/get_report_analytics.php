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

    $query = "SELECT e.overall_rating, e.created_at, e.faculty_id, e.student_id,
                     COALESCE(ep.id, 0) AS resolved_period_id,
                     f.firstname, f.lastname, f.suffix,
                     p.program_code, p.program_name, ep.ay AS academic_year, ep.semester
              FROM evaluations e
              INNER JOIN add_faculties f ON f.id = e.faculty_id
              LEFT JOIN add_subjects s ON s.id = e.subject_id
              LEFT JOIN add_programs p ON p.id = s.program_id
              LEFT JOIN evaluation_periods ep
                ON TRIM(ep.ay) = TRIM(e.academic_year)
               AND LOWER(TRIM(ep.semester)) = LOWER(TRIM(e.semester))";
    if ($where) {
        $query .= " WHERE " . implode(" AND ", $where);
    }
    $query .= " ORDER BY ep.ay ASC, ep.semester ASC, e.created_at ASC";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Unable to prepare report analytics query.");
    }
    if ($types !== "") {
        bindDynamicParams($stmt, $types, $params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $distribution = [
        "Excellent" => 0,
        "Very Good" => 0,
        "Good" => 0,
        "Fair" => 0,
        "Poor" => 0,
    ];
    $programs = [];
    $trend = [];
    $faculty = [];
    $facultyPeriodIds = [];
    $total = 0;

    while ($row = $result->fetch_assoc()) {
        $score = (float) $row["overall_rating"];
        $facultyId = intval($row["faculty_id"]);
        $name = trim(
            $row["firstname"] . " " . $row["lastname"] . " " . $row["suffix"],
        );
        if (!isset($faculty[$facultyId])) {
            $faculty[$facultyId] = [
                "name" => $name,
                "period_ids" => [],
                "student_ids" => [],
                "period_students" => [],
            ];
        }
        $resolvedPeriodId = intval($row["resolved_period_id"]);
        $studentId = intval($row["student_id"]);
        $faculty[$facultyId]["period_ids"][$resolvedPeriodId] = true;
        $faculty[$facultyId]["student_ids"][$studentId] = true;
        $faculty[$facultyId]["period_students"][$resolvedPeriodId][
            $studentId
        ] = true;
        if ($score <= 0) {
            continue;
        }
        $total++;
        $rating =
            $score >= 4.5
                ? "Excellent"
                : ($score >= 3.5
                    ? "Very Good"
                    : ($score >= 2.5
                        ? "Good"
                        : ($score >= 1.5
                            ? "Fair"
                            : "Poor")));

        $facultyPeriodIds[$facultyId][
            intval($row["resolved_period_id"])
        ] = true;

        $program =
            trim((string) ($row["program_code"] ?? "")) ?: "Unassigned Program";
        if (!isset($programs[$program])) {
            $programs[$program] = [
                "sum" => 0,
                "count" => 0,
                "respondents" => [],
            ];
        }
        $programs[$program]["sum"] += $score;
        $programs[$program]["count"]++;
        $programs[$program]["respondents"][intval($row["student_id"])] = true;

        $period = trim((string) ($row["academic_year"] ?? ""));
        if ($period !== "" && ($row["semester"] ?? "") !== "") {
            $period .= " - " . $row["semester"];
        }
        if ($period === "") {
            $period = date("Y-m", strtotime($row["created_at"]));
        }
        if (!isset($trend[$period])) {
            $trend[$period] = ["sum" => 0, "count" => 0, "respondents" => []];
        }
        $trend[$period]["sum"] += $score;
        $trend[$period]["count"]++;
        $trend[$period]["respondents"][intval($row["student_id"])] = true;
    }
    $stmt->close();

    $toAverages = static function (array $items): array {
        $output = [];
        foreach ($items as $label => $item) {
            $output[] = [
                "label" => $label,
                "average" => round($item["sum"] / $item["count"], 2),
                "responses" => $item["count"],
            ];
        }
        usort($output, static fn($a, $b) => $b["average"] <=> $a["average"]);
        return $output;
    };

    $distribution = [
        "Excellent" => 0,
        "Very Good" => 0,
        "Good" => 0,
        "Fair" => 0,
        "Poor" => 0,
    ];
    $distributionFacultyCount = 0;
    foreach ($facultyPeriodIds as $facultyId => $periodIds) {
        $periodIds = array_values(
            array_filter(array_keys($periodIds), static fn($id) => $id > 0),
        );
        $scores = array_map(
            static fn($periodId) => calcWeightedScore(
                $conn,
                $facultyId,
                null,
                null,
                (int) $periodId,
            ),
            $periodIds,
        );
        $scores = array_values(
            array_filter($scores, static fn($score) => $score > 0),
        );
        $score = empty($scores)
            ? 0.0
            : round(array_sum($scores) / count($scores), 2);
        if ($score <= 0) {
            continue;
        }

        $distributionFacultyCount++;
        $rating =
            $score >= 4.5
                ? "Excellent"
                : ($score >= 3.5
                    ? "Very Good"
                    : ($score >= 2.5
                        ? "Good"
                        : ($score >= 1.5
                            ? "Fair"
                            : "Poor")));
        $distribution[$rating]++;
    }
    $total = $distributionFacultyCount;

    $programData = $toAverages($programs);
    $scoreForFaculty = static function (int $facultyId, array $periodIds) use (
        $conn,
    ): float {
        $periodIds = array_values(
            array_filter(array_keys($periodIds), static fn($id) => $id > 0),
        );
        if (empty($periodIds)) {
            return 0.0;
        }

        $scores = array_map(
            static fn($periodId) => calcWeightedScore(
                $conn,
                $facultyId,
                null,
                null,
                (int) $periodId,
            ),
            $periodIds,
        );
        $scores = array_values(
            array_filter($scores, static fn($score) => $score > 0),
        );

        return empty($scores)
            ? 0.0
            : round(array_sum($scores) / count($scores), 2);
    };

    $rankingData = [];
    foreach ($faculty as $facultyId => $facultyData) {
        $score = $scoreForFaculty((int) $facultyId, $facultyData["period_ids"]);
        if ($score > 0) {
            $rankingData[] = [
                "label" => $facultyData["name"],
                "average" => $score,
                "responses" => count($facultyData["student_ids"]),
            ];
        }
    }
    usort($rankingData, static fn($a, $b) => $b["average"] <=> $a["average"]);
    $trendData = $toAverages($trend);
    usort($trendData, static fn($a, $b) => strcmp($a["label"], $b["label"]));

    echo json_encode([
        "success" => true,
        "data" => [
            "total" => $total,
            "distribution" => $distribution,
            "programs" => $programData,
            "trend" => $trendData,
            "rankings" => $rankingData,
        ],
    ]);
} catch (Throwable $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error fetching report analytics: " . $e->getMessage(),
    ]);
}

$conn->close();
?>

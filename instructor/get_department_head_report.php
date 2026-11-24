<?php
require_once __DIR__ . "/../shared/security.php";
requireRole("faculty");
requireMethod("GET");
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

include __DIR__ . "/../shared/connect.php";
require_once __DIR__ . "/../shared/ensure_schema_column.php";
require_once __DIR__ . "/../shared/department_schema.php";
require_once __DIR__ . "/../shared/evaluation_schema.php";
require_once __DIR__ . "/../shared/weighted_score_helper.php";

function departmentHeadRatingLabel(float $score): string
{
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

function departmentHeadPrepare(mysqli $conn, string $sql): mysqli_stmt
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException("Unable to prepare department report query.");
    }
    return $stmt;
}

try {
    ensureColumnExists(
        $conn,
        "add_faculties",
        "department",
        "VARCHAR(120) DEFAULT NULL",
        "suffix",
    );
    ensureColumnExists(
        $conn,
        "add_faculties",
        "is_department_head",
        "TINYINT(1) NOT NULL DEFAULT 0",
        "department",
    );
    ensureDepartmentSchema($conn);
    ensureEvaluationsSchema($conn);

    $facultyId = (int) ($_SESSION["id"] ?? 0);
    $headStmt = departmentHeadPrepare($conn, "
        SELECT d.id, d.department_name
        FROM add_faculties f
        INNER JOIN add_departments d ON d.department_name = f.department
        WHERE f.id = ?
          AND COALESCE(f.is_department_head, 0) = 1
          AND LOWER(TRIM(COALESCE(f.status, 'active'))) = 'active'
        LIMIT 2
    ");
    $headStmt->bind_param("i", $facultyId);
    if (!$headStmt->execute()) {
        throw new RuntimeException("Unable to validate department head access.");
    }
    $headResult = $headStmt->get_result();
    $headRows = [];
    while ($row = $headResult->fetch_assoc()) {
        $headRows[] = $row;
    }
    $headStmt->close();

    if (count($headRows) !== 1) {
        http_response_code(count($headRows) > 1 ? 409 : 403);
        echo json_encode([
            "success" => false,
            "message" =>
                count($headRows) > 1
                    ? "The department assignment is ambiguous. Contact an administrator."
                    : "Department Head access is not assigned to this account.",
        ]);
        exit();
    }

    $departmentId = (int) $headRows[0]["id"];
    $departmentName = (string) $headRows[0]["department_name"];
    $periodStmt = departmentHeadPrepare($conn, "
        SELECT DISTINCT ep.id, ep.ay, ep.semester
        FROM evaluation_periods ep
        INNER JOIN evaluations e
            ON TRIM(e.academic_year) = TRIM(ep.ay)
           AND LOWER(TRIM(e.semester)) = LOWER(TRIM(ep.semester))
        INNER JOIN add_classes ac ON ac.id = e.class_id
        INNER JOIN add_programs p ON p.id = ac.program_id
        INNER JOIN add_subjects s
            ON s.id = e.subject_id AND s.program_id = p.id
        WHERE p.department_id = ?
          AND ep.end_date IS NOT NULL
          AND (ep.end_date < CURDATE() OR ep.is_active = 0)
        ORDER BY ep.id DESC
    ");
    $periodStmt->bind_param("i", $departmentId);
    if (!$periodStmt->execute()) {
        throw new RuntimeException("Unable to load closed evaluation periods.");
    }
    $periodResult = $periodStmt->get_result();
    $periods = [];
    while ($row = $periodResult->fetch_assoc()) {
        $periods[] = [
            "id" => (int) $row["id"],
            "academic_year" => (string) $row["ay"],
            "semester" => (string) $row["semester"],
            "label" => trim($row["ay"] . " - " . $row["semester"]),
        ];
    }
    $periodStmt->close();

    $requestedAcademicYear = trim(
        (string) ($_GET["academic_year"] ?? ""),
    );
    $requestedSemester = trim((string) ($_GET["semester"] ?? ""));
    $selectedPeriod = null;
    foreach ($periods as $period) {
        if (
            ($requestedAcademicYear === "" ||
                $period["academic_year"] === $requestedAcademicYear) &&
            ($requestedSemester === "" ||
                strcasecmp($period["semester"], $requestedSemester) === 0)
        ) {
            $selectedPeriod = $period;
            break;
        }
    }
    if ($requestedAcademicYear === "" && $requestedSemester === "") {
        $selectedPeriod = $periods[0] ?? null;
    } elseif ($selectedPeriod === null) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "The selected evaluation period is unavailable.",
        ]);
        exit();
    }

    $academicYears = array_values(
        array_unique(array_column($periods, "academic_year")),
    );
    $semesters = [];
    foreach ($periods as $period) {
        if (
            $selectedPeriod === null ||
            $period["academic_year"] === $selectedPeriod["academic_year"]
        ) {
            $semesters[] = $period["semester"];
        }
    }
    $semesters = array_values(array_unique($semesters));

    $facultyReports = [];
    if ($selectedPeriod !== null) {
        $filters = [
            "p.department_id = ?",
            "TRIM(f.department) = TRIM(?)",
            "TRIM(e.academic_year) = ?",
            "LOWER(TRIM(e.semester)) = LOWER(?)",
        ];
        $params = [
            $departmentId,
            $departmentName,
            $selectedPeriod["academic_year"],
            $selectedPeriod["semester"],
        ];
        $types = "isss";

        $reportStmt = departmentHeadPrepare($conn, "
            SELECT
                f.id AS faculty_record_id,
                f.faculty_id,
                CONCAT_WS(' ', NULLIF(TRIM(f.firstname), ''),
                    NULLIF(TRIM(f.lastname), ''),
                    NULLIF(TRIM(f.suffix), '')) AS faculty_name,
                c.id AS category_id,
                c.category_name,
                COALESCE(c.weight, 0) AS category_weight,
                AVG(ea.rating) AS category_average,
                COUNT(DISTINCT e.student_id) AS respondent_count
            FROM evaluations e
            INNER JOIN add_faculties f ON f.id = e.faculty_id
            INNER JOIN add_classes ac ON ac.id = e.class_id
            INNER JOIN add_programs p ON p.id = ac.program_id
            INNER JOIN add_subjects s
                ON s.id = e.subject_id AND s.program_id = p.id
            LEFT JOIN evaluation_answers ea ON ea.evaluation_id = e.id
            LEFT JOIN add_questions q ON q.id = ea.question_id
            LEFT JOIN add_categories c ON c.id = q.category_id
            WHERE " . implode(" AND ", $filters) . "
            GROUP BY
                f.id, f.faculty_id, f.firstname, f.lastname, f.suffix,
                c.id, c.category_name, c.weight
            ORDER BY f.lastname ASC, f.firstname ASC
        ");
        bindDynamicParams($reportStmt, $types, $params);
        if (!$reportStmt->execute()) {
            throw new RuntimeException("Unable to load department evaluation results.");
        }
        $reportResult = $reportStmt->get_result();
        $facultyGroups = [];
        while ($row = $reportResult->fetch_assoc()) {
            $key = (int) $row["faculty_record_id"];
            if (!isset($facultyGroups[$key])) {
                $facultyGroups[$key] = [
                    "faculty_record_id" => $key,
                    "faculty_id" => (string) $row["faculty_id"],
                    "name" => trim((string) $row["faculty_name"]),
                    "respondents" => (int) $row["respondent_count"],
                    "categories" => [],
                ];
            }
            if (
                $row["category_id"] !== null &&
                $row["category_average"] !== null
            ) {
                $facultyGroups[$key]["categories"][] = [
                    "name" => (string) $row["category_name"],
                    "weight" => (float) $row["category_weight"],
                    "average" => (float) $row["category_average"],
                    "responses" => (int) $row["respondent_count"],
                ];
            }
        }
        $reportStmt->close();

        foreach ($facultyGroups as $faculty) {
            $score = 0.0;
            $categoryReports = [];
            if ($faculty["categories"] !== []) {
                $weights = array_column($faculty["categories"], "weight");
                $normalizedWeights = normaliseCategoryWeights($weights);
                foreach ($faculty["categories"] as $index => $category) {
                    $score +=
                        $category["average"] *
                        ($normalizedWeights[$index] / 100);
                    $categoryReports[] = [
                        "name" => $category["name"],
                        "average" => round($category["average"], 2),
                        "weight" => round($normalizedWeights[$index], 2),
                        "responses" => $category["responses"],
                    ];
                }
                $score = round($score, 2);
            }
            $hasScore = $faculty["categories"] !== [];

            $facultyReports[] = [
                "faculty_record_id" => $faculty["faculty_record_id"],
                "faculty_id" => $faculty["faculty_id"],
                "name" => $faculty["name"] !== ""
                    ? $faculty["name"]
                    : "Unnamed faculty",
                "score" => $hasScore ? $score : null,
                "percentage" => $hasScore
                    ? round(($score / 5) * 100, 1)
                    : null,
                "rating" => $hasScore
                    ? departmentHeadRatingLabel($score)
                    : "No score",
                "responses" => $faculty["respondents"],
                "categories" => $categoryReports,
            ];
        }
    }

    echo json_encode([
        "success" => true,
        "department" => (string) $headRows[0]["department_name"],
        "periods" => $periods,
        "selected_period_id" => $selectedPeriod["id"] ?? null,
        "selected_filters" => [
            "academic_year" => $selectedPeriod["academic_year"] ?? "",
            "semester" => $selectedPeriod["semester"] ?? "",
        ],
        "filters" => [
            "academic_years" => $academicYears,
            "semesters" => $semesters,
        ],
        "faculty" => $facultyReports,
    ]);
} catch (Throwable $error) {
    error_log("Department head report error: " . $error->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Unable to load department evaluation results.",
    ]);
}

$conn->close();
?>

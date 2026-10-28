<?php
require_once __DIR__ . "/../shared/security.php";
requireRole("admin");
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include __DIR__ . "/../shared/connect.php";
require_once __DIR__ . "/../shared/weighted_score_helper.php";
require_once __DIR__ . "/../shared/evaluation_schema.php";

function getActiveEvaluationPeriodId(mysqli $conn): int
{
    $result = $conn->query(
        "SELECT active_period_id FROM evaluation_settings WHERE id = 1 LIMIT 1",
    );
    $row = $result ? $result->fetch_assoc() : null;
    return intval($row["active_period_id"] ?? 0);
}

try {
    ensureEvaluationsSchema($conn);
    $active_period_id = getActiveEvaluationPeriodId($conn);
    // Get total faculty count
    $faculty_query = "SELECT COUNT(*) as total FROM add_faculties";
    $faculty_result = $conn->query($faculty_query);
    $total_faculty = $faculty_result->fetch_assoc()["total"];

    // Get total students count
    $student_query = "SELECT COUNT(*) as total FROM add_students";
    $student_result = $conn->query($student_query);
    $total_students = $student_result->fetch_assoc()["total"];

    error_log("Dashboard stats - Total students: " . $total_students);

    // Get total active students.
    $active_students_query =
        "SELECT COUNT(*) as total FROM add_students WHERE LOWER(TRIM(COALESCE(status,'active'))) = 'active'";
    $active_students_result = $conn->query($active_students_query);
    $total_active_students = $active_students_result->fetch_assoc()["total"];

    $expected_evaluations = 0;
    $total_evaluations_submitted = 0;

    // A student is complete only after submitting every assigned evaluation.
    if ($active_period_id > 0) {
        $evaluation_stmt = $conn->prepare("
            SELECT
                COUNT(*) AS expected_students,
                COALESCE(SUM(student_progress.pending_assignments = 0), 0) AS completed_students
            FROM (
                SELECT
                    st.id,
                    COUNT(DISTINCT CASE
                        WHEN ev.id IS NULL THEN CONCAT(cs.faculty_id, ':', cs.subject_id, ':', ac.id)
                    END) AS pending_assignments
                FROM add_students st
                INNER JOIN evaluation_periods ep ON ep.id = ?
                INNER JOIN add_classes ac
                    ON LOWER(TRIM(ac.semester)) = LOWER(TRIM(ep.semester))
                   AND (
                       CAST(ac.program_id AS CHAR) = TRIM(st.program)
                       OR EXISTS (
                           SELECT 1
                           FROM add_programs p
                           WHERE p.id = ac.program_id
                             AND (
                                 TRIM(p.program_code) = TRIM(st.program)
                                 OR TRIM(p.program_name) = TRIM(st.program)
                             )
                       )
                   )
                   AND (
                       ac.year_level = st.yearlevel
                       OR ac.year_level = CONCAT(
                           st.yearlevel,
                           CASE st.yearlevel
                               WHEN '1' THEN 'st Year'
                               WHEN '2' THEN 'nd Year'
                               WHEN '3' THEN 'rd Year'
                               ELSE 'th Year'
                           END
                       )
                   )
                   AND TRIM(UPPER(ac.block)) = TRIM(UPPER(st.section))
                INNER JOIN class_subjects cs ON cs.class_id = ac.id AND cs.faculty_id > 0
                INNER JOIN add_faculties f ON f.id = cs.faculty_id
                INNER JOIN add_subjects s
                    ON s.id = cs.subject_id
                   AND LOWER(TRIM(s.semester)) = LOWER(TRIM(ep.semester))
                LEFT JOIN evaluations ev
                    ON ev.student_id = st.id
                   AND ev.faculty_id = cs.faculty_id
                   AND ev.subject_id = cs.subject_id
                   AND ev.class_id = ac.id
                   AND TRIM(ev.academic_year) = TRIM(ep.ay)
                   AND LOWER(TRIM(ev.semester)) = LOWER(TRIM(ep.semester))
                WHERE LOWER(TRIM(COALESCE(st.status, 'active'))) = 'active'
                GROUP BY st.id
            ) AS student_progress
        ");
        if (!$evaluation_stmt) {
            throw new Exception("Unable to prepare evaluation progress query.");
        }
        $evaluation_stmt->bind_param("i", $active_period_id);
        $evaluation_stmt->execute();
        $evaluation_result = $evaluation_stmt->get_result();
        $progress = $evaluation_result->fetch_assoc() ?: [];
        $expected_evaluations = intval($progress["expected_students"] ?? 0);
        $total_evaluations_submitted = intval(
            $progress["completed_students"] ?? 0,
        );
        $evaluation_stmt->close();
    }

    // Get faculty ratings using weighted scores (real-time)
    $faculty_list_query =
        "
        SELECT f.id, f.firstname, f.lastname, f.suffix, COUNT(e.id) as response_count
        FROM add_faculties f
        LEFT JOIN evaluations e
          ON f.id = e.faculty_id
         " .
        ($active_period_id > 0 ? "AND EXISTS (SELECT 1 FROM evaluation_periods ep_filter WHERE ep_filter.id = ? AND TRIM(ep_filter.ay) = TRIM(e.academic_year) AND LOWER(TRIM(ep_filter.semester)) = LOWER(TRIM(e.semester)))" : "") .
        "
        GROUP BY f.id, f.firstname, f.lastname, f.suffix
        HAVING response_count > 0
    ";
    if ($active_period_id > 0) {
        $faculty_list_stmt = $conn->prepare($faculty_list_query);
        $faculty_list_stmt->bind_param("i", $active_period_id);
        $faculty_list_stmt->execute();
        $faculty_list_result = $faculty_list_stmt->get_result();
    } else {
        $faculty_list_result = $conn->query($faculty_list_query);
        $faculty_list_stmt = null;
    }

    $ratings = [];
    $weighted_sum = 0.0;
    $evaluated_faculty = 0;

    while ($frow = $faculty_list_result->fetch_assoc()) {
        $fid = intval($frow["id"]);
        $weighted_avg = calcWeightedScore(
            $conn,
            $fid,
            null,
            null,
            $active_period_id ?: null,
        );
        if ($weighted_avg <= 0) {
            continue;
        }
        $weighted_sum += $weighted_avg;
        $evaluated_faculty++;
        $ratings[] = [
            "name" => trim(
                $frow["firstname"] .
                    " " .
                    $frow["lastname"] .
                    " " .
                    $frow["suffix"],
            ),
            "rating" => round($weighted_avg, 2),
            "percentage" => round(($weighted_avg / 5) * 100, 2),
            "responses" => intval($frow["response_count"]),
        ];
    }
    if ($faculty_list_stmt) {
        $faculty_list_stmt->close();
    }

    // Sort by weighted rating descending
    usort($ratings, fn($a, $b) => $b["rating"] <=> $a["rating"]);

    $overall_rating =
        $evaluated_faculty > 0
            ? round($weighted_sum / $evaluated_faculty, 2)
            : 0;

    // Get department/program data for graph (based on subject assignments)
    $dept_query = "
        SELECT
            p.program_name AS department,
            COUNT(DISTINCT fs.faculty_id) AS faculty_count
        FROM add_programs p
        LEFT JOIN add_subjects s ON s.program_id = p.id
        LEFT JOIN faculty_subjects fs ON fs.subject_id = s.id
        GROUP BY p.id, p.program_name
        ORDER BY faculty_count DESC
    ";
    $dept_result = $conn->query($dept_query);

    $departments = [];
    while ($row = $dept_result->fetch_assoc()) {
        $departments[] = [
            "name" => $row["department"],
            "count" => $row["faculty_count"],
        ];
    }

    // Rating distribution based on weighted scores
    $ratingDistribution = [
        "Excellent" => 0,
        "Very Good" => 0,
        "Good" => 0,
        "Fair" => 0,
        "Poor" => 0,
    ];
    foreach ($ratings as $r) {
        $s = $r["rating"];
        if ($s >= 4.5) {
            $ratingDistribution["Excellent"]++;
        } elseif ($s >= 3.5) {
            $ratingDistribution["Very Good"]++;
        } elseif ($s >= 2.5) {
            $ratingDistribution["Good"]++;
        } elseif ($s >= 1.5) {
            $ratingDistribution["Fair"]++;
        } else {
            $ratingDistribution["Poor"]++;
        }
    }

    echo json_encode([
        "success" => true,
        "data" => [
            "totalFaculty" => $total_faculty,
            "totalStudents" => $total_students,
            "activeStudents" => $total_active_students,
            "activePeriodId" => $active_period_id,
            "expectedEvaluations" => $expected_evaluations,
            "expectedStudents" => $expected_evaluations,
            "completedStudents" => $total_evaluations_submitted,
            "totalEvaluationsSubmitted" => $total_evaluations_submitted,
            "pendingEvaluations" => max(
                $expected_evaluations - $total_evaluations_submitted,
                0,
            ),
            "overallRating" => $overall_rating,
            "evaluatedFaculty" => $evaluated_faculty,
            "ratings" => $ratings,
            "departments" => $departments,
            "ratingDistribution" => $ratingDistribution,
        ],
    ]);
} catch (Throwable $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error fetching dashboard stats: " . $e->getMessage(),
    ]);
}

$conn->close();
?>

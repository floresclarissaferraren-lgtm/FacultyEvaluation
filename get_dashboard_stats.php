<?php
require_once 'security.php';
requireRole('admin');
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include 'connect.php';
require_once 'weighted_score_helper.php';
require_once 'evaluation_schema.php';

function getActiveEvaluationPeriodId(mysqli $conn): int
{
    $result = $conn->query("SELECT active_period_id FROM evaluation_settings WHERE id = 1 LIMIT 1");
    $row = $result ? $result->fetch_assoc() : null;
    return intval($row['active_period_id'] ?? 0);
}

function getExpectedEvaluationSlots(mysqli $conn): int
{
    $sql = "
        SELECT COUNT(*) AS total
        FROM (
            SELECT DISTINCT
                st.id AS student_id,
                cs.faculty_id,
                cs.subject_id,
                ac.id AS class_id
            FROM add_students st
            INNER JOIN add_classes ac
                ON (
                    CAST(ac.program_id AS CHAR) = TRIM(st.program)
                    OR EXISTS (
                        SELECT 1
                        FROM add_programs ap
                        WHERE ap.id = ac.program_id
                          AND (
                            TRIM(ap.program_code) = TRIM(st.program)
                            OR TRIM(ap.program_name) = TRIM(st.program)
                          )
                    )
                )
               AND (
                    ac.year_level = st.yearlevel
                    OR ac.year_level = CONCAT(st.yearlevel, CASE st.yearlevel WHEN '1' THEN 'st Year' WHEN '2' THEN 'nd Year' WHEN '3' THEN 'rd Year' ELSE 'th Year' END)
               )
               AND TRIM(UPPER(ac.block)) = TRIM(UPPER(st.section))
            INNER JOIN class_subjects cs
                ON cs.class_id = ac.id
               AND cs.faculty_id > 0
            WHERE LOWER(TRIM(COALESCE(st.status, 'active'))) = 'active'

            UNION

            SELECT DISTINCT
                ss.student_id,
                COALESCE(NULLIF(cs.faculty_id, 0), fs.faculty_id) AS faculty_id,
                ss.subject_id,
                COALESCE(ssc.class_id, 0) AS class_id
            FROM student_subjects ss
            INNER JOIN add_students st
                ON st.id = ss.student_id
               AND LOWER(TRIM(COALESCE(st.status, 'active'))) = 'active'
            LEFT JOIN student_subject_classes ssc
                ON ssc.student_id = ss.student_id
               AND ssc.subject_id = ss.subject_id
            LEFT JOIN class_subjects cs
                ON cs.class_id = ssc.class_id
               AND cs.subject_id = ss.subject_id
               AND cs.faculty_id > 0
            LEFT JOIN faculty_subjects fs
                ON fs.subject_id = ss.subject_id
            WHERE COALESCE(NULLIF(cs.faculty_id, 0), fs.faculty_id) IS NOT NULL
        ) expected
    ";
    $result = $conn->query($sql);
    $row = $result ? $result->fetch_assoc() : null;
    return intval($row['total'] ?? 0);
}

try {
    ensureEvaluationsSchema($conn);
    $active_period_id = getActiveEvaluationPeriodId($conn);
    // Get total faculty count
    $faculty_query = "SELECT COUNT(*) as total FROM add_faculties";
    $faculty_result = $conn->query($faculty_query);
    $total_faculty = $faculty_result->fetch_assoc()['total'];
    
    // Get total students count
    $student_query = "SELECT COUNT(*) as total FROM add_students";
    $student_result = $conn->query($student_query);
    $total_students = $student_result->fetch_assoc()['total'];
    
    error_log("Dashboard stats - Total students: " . $total_students);
    
    // Get total active students.
    $active_students_query = "SELECT COUNT(*) as total FROM add_students WHERE LOWER(TRIM(COALESCE(status,'active'))) = 'active'";
    $active_students_result = $conn->query($active_students_query);
    $total_active_students = $active_students_result->fetch_assoc()['total'];

    $expected_evaluations = $total_active_students > 0 ? $total_active_students : getExpectedEvaluationSlots($conn);

    // Get submitted evaluations for the active period when one is set.
    if ($active_period_id > 0) {
        $evaluation_stmt = $conn->prepare("SELECT COUNT(*) as total FROM evaluations WHERE period_id = ?");
        $evaluation_stmt->bind_param("i", $active_period_id);
        $evaluation_stmt->execute();
        $evaluation_result = $evaluation_stmt->get_result();
        $total_evaluations_submitted = intval($evaluation_result->fetch_assoc()['total'] ?? 0);
        $evaluation_stmt->close();
    } else {
        $evaluation_query = "SELECT COUNT(*) as total FROM evaluations";
        $evaluation_result = $conn->query($evaluation_query);
        $total_evaluations_submitted = intval($evaluation_result->fetch_assoc()['total'] ?? 0);
    }
    
    // Get faculty ratings using weighted scores (real-time)
    $faculty_list_query = "
        SELECT f.id, f.firstname, f.lastname, f.suffix, COUNT(e.id) as response_count
        FROM add_faculties f
        LEFT JOIN evaluations e
          ON f.id = e.faculty_id
         " . ($active_period_id > 0 ? "AND e.period_id = ?" : "") . "
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

    $ratings            = [];
    $weighted_sum       = 0.0;
    $evaluated_faculty  = 0;

    while ($frow = $faculty_list_result->fetch_assoc()) {
        $fid          = intval($frow['id']);
        $weighted_avg = calcWeightedScore($conn, $fid, null, null, $active_period_id ?: null);
        if ($weighted_avg <= 0) {
            continue;
        }
        $weighted_sum += $weighted_avg;
        $evaluated_faculty++;
        $ratings[] = [
            'name'       => trim($frow['firstname'] . ' ' . $frow['lastname'] . ' ' . $frow['suffix']),
            'rating'     => round($weighted_avg, 2),
            'percentage' => round(($weighted_avg / 5) * 100, 2),
            'responses'  => intval($frow['response_count'])
        ];
    }
    if ($faculty_list_stmt) {
        $faculty_list_stmt->close();
    }

    // Sort by weighted rating descending
    usort($ratings, fn($a, $b) => $b['rating'] <=> $a['rating']);

    $overall_rating = $evaluated_faculty > 0
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
            'name' => $row['department'],
            'count' => $row['faculty_count']
        ];
    }
    
    // Rating distribution based on weighted scores
    $ratingDistribution = [
        'Excellent' => 0,
        'Very Good' => 0,
        'Good'      => 0,
        'Fair'      => 0,
        'Poor'      => 0
    ];
    foreach ($ratings as $r) {
        $s = $r['rating'];
        if ($s >= 4.5)      $ratingDistribution['Excellent']++;
        elseif ($s >= 3.5)  $ratingDistribution['Very Good']++;
        elseif ($s >= 2.5)  $ratingDistribution['Good']++;
        elseif ($s >= 1.5)  $ratingDistribution['Fair']++;
        else                $ratingDistribution['Poor']++;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'totalFaculty' => $total_faculty,
            'totalStudents' => $total_students,
            'activeStudents' => $total_active_students,
            'activePeriodId' => $active_period_id,
            'expectedEvaluations' => $expected_evaluations,
            'totalEvaluationsSubmitted' => $total_evaluations_submitted,
            'pendingEvaluations' => max($expected_evaluations - $total_evaluations_submitted, 0),
            'overallRating' => $overall_rating,
            'evaluatedFaculty' => $evaluated_faculty,
            'ratings' => $ratings,
            'departments' => $departments,
            'ratingDistribution' => $ratingDistribution
        ]
    ]);
    
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching dashboard stats: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

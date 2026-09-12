<?php
require_once 'security.php';
requireRole('admin');
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include 'connect.php';
require_once 'weighted_score_helper.php';
require_once 'evaluation_schema.php';

try {
    ensureEvaluationsSchema($conn);
    // Get total faculty count
    $faculty_query = "SELECT COUNT(*) as total FROM add_faculties";
    $faculty_result = $conn->query($faculty_query);
    $total_faculty = $faculty_result->fetch_assoc()['total'];
    
    // Get total students count
    $student_query = "SELECT COUNT(*) as total FROM add_students";
    $student_result = $conn->query($student_query);
    $total_students = $student_result->fetch_assoc()['total'];
    
    error_log("Dashboard stats - Total students: " . $total_students);
    
    // Get total active students (who need to evaluate)
    $active_students_query = "SELECT COUNT(*) as total FROM add_students WHERE LOWER(TRIM(COALESCE(status,'active'))) = 'active'";
    $active_students_result = $conn->query($active_students_query);
    $total_active_students = $active_students_result->fetch_assoc()['total'];

    // Get total submitted evaluations
    $evaluation_query = "SELECT COUNT(*) as total FROM evaluations";
    $evaluation_result = $conn->query($evaluation_query);
    $total_evaluations_submitted = $evaluation_result->fetch_assoc()['total'];
    
    // Get faculty ratings using weighted scores (real-time)
    $faculty_list_query = "
        SELECT f.id, f.firstname, f.lastname, f.suffix, COUNT(e.id) as response_count
        FROM add_faculties f
        LEFT JOIN evaluations e ON f.id = e.faculty_id
        GROUP BY f.id, f.firstname, f.lastname, f.suffix
        HAVING response_count > 0
    ";
    $faculty_list_result = $conn->query($faculty_list_query);

    $ratings            = [];
    $weighted_sum       = 0.0;
    $evaluated_faculty  = 0;

    while ($frow = $faculty_list_result->fetch_assoc()) {
        $fid          = intval($frow['id']);
        $weighted_avg = calcWeightedScore($conn, $fid);
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
            'totalEvaluationsSubmitted' => $total_evaluations_submitted,
            'overallRating' => $overall_rating,
            'evaluatedFaculty' => $evaluated_faculty,
            'ratings' => $ratings,
            'departments' => $departments,
            'ratingDistribution' => $ratingDistribution
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching dashboard stats: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

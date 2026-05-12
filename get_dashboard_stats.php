<?php
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include 'connect.php';

try {
    // Get total faculty count
    $faculty_query = "SELECT COUNT(*) as total FROM add_faculties";
    $faculty_result = $conn->query($faculty_query);
    $total_faculty = $faculty_result->fetch_assoc()['total'];
    
    // Get total students count
    $student_query = "SELECT COUNT(*) as total FROM add_students";
    $student_result = $conn->query($student_query);
    $total_students = $student_result->fetch_assoc()['total'];
    
    error_log("Dashboard stats - Total students: " . $total_students);
    
    // Get total evaluations count
    $evaluation_query = "SELECT COUNT(*) as total FROM evaluations";
    $evaluation_result = $conn->query($evaluation_query);
    $total_evaluations = $evaluation_result->fetch_assoc()['total'];
    
    // Get overall faculty rating
    $overall_rating_query = "
        SELECT AVG(e.overall_rating) as overall_avg
        FROM evaluations e
        WHERE e.overall_rating IS NOT NULL
    ";
    $overall_rating_result = $conn->query($overall_rating_query);
    $overall_rating_row = $overall_rating_result->fetch_assoc();
    $overall_rating = $overall_rating_row ? round($overall_rating_row['overall_avg'], 2) : 0;
    
    // Get faculty ratings for dashboard
    $ratings_query = "
        SELECT 
            f.firstname,
            f.lastname,
            f.suffix,
            AVG(e.overall_rating) as avg_rating,
            COUNT(e.id) as response_count
        FROM add_faculties f
        LEFT JOIN evaluations e ON f.faculty_id = e.faculty_id
        GROUP BY f.id, f.firstname, f.lastname, f.suffix
        HAVING AVG(e.overall_rating) IS NOT NULL
        ORDER BY avg_rating DESC
        LIMIT 5
    ";
    $ratings_result = $conn->query($ratings_query);
    
    $ratings = [];
    while ($row = $ratings_result->fetch_assoc()) {
        $ratings[] = [
            'name' => trim($row['firstname'] . ' ' . $row['lastname'] . ' ' . $row['suffix']),
            'rating' => round($row['avg_rating'], 2),
            'responses' => $row['response_count']
        ];
    }
    
    // Get department data for graph
    $dept_query = "
        SELECT 
            p.program_name as department,
            COUNT(DISTINCT f.id) as faculty_count
        FROM add_programs p
        LEFT JOIN add_faculties f ON p.id = f.program
        WHERE f.id IS NOT NULL
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
    
    echo json_encode([
        'success' => true,
        'data' => [
            'totalFaculty' => $total_faculty,
            'totalStudents' => $total_students,
            'totalEvaluations' => $total_evaluations,
            'overallRating' => $overall_rating,
            'ratings' => $ratings,
            'departments' => $departments
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

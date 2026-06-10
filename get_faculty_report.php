<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Check if connect.php exists
if (!file_exists('connect.php')) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection file not found'
    ]);
    exit;
}

include 'connect.php';
require_once 'weighted_score_helper.php';

function getRatingLabel($score) {
    $score = floatval($score);
    if ($score <= 0) return 'No Responses';
    if ($score >= 4.5) return 'Outstanding';
    if ($score >= 3.5) return 'Very Good';
    if ($score >= 2.5) return 'Good';
    if ($score >= 1.5) return 'Fair';
    return 'Poor';
}

function getRatingClass($score) {
    $score = floatval($score);
    if ($score <= 0) return 'no-responses';
    if ($score >= 4.5) return 'outstanding';
    if ($score >= 3.5) return 'very-good';
    if ($score >= 2.5) return 'good';
    if ($score >= 1.5) return 'fair';
    return 'poor';
}

if (!isset($_GET['faculty_id']) || empty($_GET['faculty_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Faculty ID is required'
    ]);
    exit;
}

$faculty_id = $_GET['faculty_id'];

try {
    // Get faculty basic information
    $faculty_query = "SELECT id, CONCAT(firstname, ' ', lastname, ' ', suffix) as name, faculty_id 
                   FROM add_faculties WHERE id = ?";
    $faculty_stmt = $conn->prepare($faculty_query);
    $faculty_stmt->bind_param("i", $faculty_id);
    $faculty_stmt->execute();
    $faculty_result = $faculty_stmt->get_result();
    
    if ($faculty_result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Faculty not found'
        ]);
        exit;
    }
    
    $faculty = $faculty_result->fetch_assoc();
    
    // Get overall rating, total responses, and period range from evaluations
    $overall_query = "SELECT 
                        COUNT(id) as total_responses,
                        MIN(DATE(created_at)) as date_from,
                        MAX(DATE(created_at)) as date_to
                    FROM evaluations
                    WHERE faculty_id = ?";
    $overall_stmt = $conn->prepare($overall_query);
    $overall_stmt->bind_param("i", $faculty_id);
    $overall_stmt->execute();
    $overall_result = $overall_stmt->get_result();
    $overall_data   = $overall_result->fetch_assoc();

    // Weighted overall score (real-time)
    $totalResponses = intval($overall_data['total_responses'] ?? 0);
    $overallScore   = $totalResponses > 0 ? calcWeightedScore($conn, intval($faculty_id)) : 0.00;
    
    // Create evaluations table if it doesn't exist
    $conn->query("
        CREATE TABLE IF NOT EXISTS evaluations (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            student_id INT(11) NOT NULL,
            faculty_id INT(11) NOT NULL,
            overall_rating DECIMAL(4,2) NOT NULL DEFAULT 0.00,
            feedback TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_student_faculty (student_id, faculty_id),
            KEY idx_eval_faculty (faculty_id),
            KEY idx_eval_student (student_id)
        )
    ");

    // Get all feedback comments for this faculty.
    $feedback_query = "SELECT feedback
                    FROM evaluations
                    WHERE faculty_id = ?
                    AND feedback IS NOT NULL
                    AND TRIM(feedback) != ''
                    ORDER BY updated_at DESC, created_at DESC";
    $feedback_stmt = $conn->prepare($feedback_query);
    $feedback_stmt->bind_param("i", $faculty_id);
    $feedback_stmt->execute();
    $feedback_result = $feedback_stmt->get_result();

    $feedback_comments = [];
    while ($feedback_row = $feedback_result->fetch_assoc()) {
        $feedback_comments[] = trim($feedback_row['feedback']);
    }
    $feedback_stmt->close();

    $all_feedback = empty($feedback_comments) ? 'No feedback available' : implode("\n\n", $feedback_comments);

    // Get evaluation details by category with weights (real-time).
    $category_stats  = getCategoryStats($conn, intval($faculty_id));
    $evaluation_details = [];
    $category_totals    = [];
    foreach ($category_stats as $cat) {
        $avg   = floatval($cat['avg_rating']);
        $label = getRatingLabel($avg);
        $class = getRatingClass($avg);
        $evaluation_details[] = [
            'category'     => $cat['category_name'],
            'average_score'=> $cat['avg_rating'],
            'rating'       => $label,
            'rating_class' => $class,
            'responses'    => $cat['responses'],
            'weight'       => $cat['weight'],
            'normalised_weight' => $cat['normalised_weight'],
            'all_feedback' => $all_feedback,
        ];
        $category_totals[] = [
            'category_name'     => $cat['category_name'],
            'avg_rating'        => $cat['avg_rating'],
            'responses'         => $cat['responses'],
            'rating'            => $label,
            'rating_class'      => $class,
            'weight'            => $cat['weight'],
            'normalised_weight' => $cat['normalised_weight'],
        ];
    }
    
    $periodText = 'All evaluation periods';
    if (!empty($overall_data['date_from']) && !empty($overall_data['date_to'])) {
        $start = date('F j, Y', strtotime($overall_data['date_from']));
        $end = date('F j, Y', strtotime($overall_data['date_to']));
        $periodText = $start === $end ? $start : "$start - $end";
    }

    $overallStatus      = getRatingLabel($overallScore);
    $overallStatusClass = getRatingClass($overallScore);

    // Prepare response data
    $response_data = [
        'id'                  => $faculty['id'],
        'name'                => $faculty['name'],
        'faculty_id'          => $faculty['faculty_id'],
        'overall_rating'      => $overallScore > 0 ? number_format($overallScore, 2) : '0.00',
        'percentage_score'    => $overallScore > 0 ? number_format(($overallScore / 5) * 100, 2) : '0.00',
        'total_responses'     => $totalResponses,
        'overall_status'      => $overallStatus,
        'overall_status_class'=> $overallStatusClass,
        'evaluation_period'   => $periodText,
        'evaluation_details'  => $evaluation_details,
        'category_totals'     => $category_totals,
        'all_feedback'        => $all_feedback,
        'feedback_comments'   => $feedback_comments
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $response_data
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

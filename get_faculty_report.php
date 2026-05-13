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
    
    // Get overall rating and total responses from evaluations
    $overall_query = "SELECT 
                        AVG(e.overall_rating) as overall_rating,
                        COUNT(e.id) as total_responses
                    FROM evaluations e
                    WHERE e.faculty_id = ?";
    $overall_stmt = $conn->prepare($overall_query);
    $overall_stmt->bind_param("i", $faculty_id);
    $overall_stmt->execute();
    $overall_result = $overall_stmt->get_result();
    $overall_data = $overall_result->fetch_assoc();
    
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

    // Get evaluation details by category (simplified version using overall ratings)
    $details_query = "SELECT 
                        'Overall Performance' as category,
                        AVG(e.overall_rating) as average_score,
                        CASE 
                            WHEN AVG(e.overall_rating) >= 4.5 THEN 'Outstanding'
                            WHEN AVG(e.overall_rating) >= 3.5 THEN 'Very Good'
                            WHEN AVG(e.overall_rating) >= 2.5 THEN 'Good'
                            WHEN AVG(e.overall_rating) >= 1.5 THEN 'Fair'
                            ELSE 'Poor'
                        END as rating,
                        CASE 
                            WHEN AVG(e.overall_rating) >= 4.5 THEN 'outstanding'
                            WHEN AVG(e.overall_rating) >= 3.5 THEN 'very-good'
                            WHEN AVG(e.overall_rating) >= 2.5 THEN 'good'
                            WHEN AVG(e.overall_rating) >= 1.5 THEN 'fair'
                            ELSE 'poor'
                        END as rating_class
                    FROM evaluations e
                    WHERE e.faculty_id = ?
                    GROUP BY e.faculty_id";
    
    $details_stmt = $conn->prepare($details_query);
    $details_stmt->bind_param("i", $faculty_id);
    $details_stmt->execute();
    $details_result = $details_stmt->get_result();
    
    $evaluation_details = [];
    while ($detail = $details_result->fetch_assoc()) {
        $evaluation_details[] = [
            'category' => $detail['category'],
            'average_score' => number_format($detail['average_score'], 2),
            'rating' => $detail['rating'],
            'rating_class' => $detail['rating_class']
        ];
    }
    
    // No sample data - use only real evaluation records
    
    // Prepare response data
    $response_data = [
        'id' => $faculty['id'],
        'name' => $faculty['name'],
        'faculty_id' => $faculty['faculty_id'],
        'overall_rating' => $overall_data['overall_rating'] ? number_format($overall_data['overall_rating'], 2) : '0.00',
        'total_responses' => $overall_data['total_responses'] ?: 0,
        'evaluation_details' => $evaluation_details
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

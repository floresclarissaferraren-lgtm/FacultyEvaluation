<?php
header("Content-Type: application/json");
include 'connect.php';

try {
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

    // Get faculty with their evaluation averages
    $query = "SELECT 
        f.id,
        f.faculty_id,
        f.firstname,
        f.lastname,
        f.suffix,
        f.email,
        COUNT(e.id) as total_responses,
        AVG(e.overall_rating) as average_score,
        MAX(e.created_at) as last_evaluation
    FROM add_faculties f
    LEFT JOIN evaluations e ON f.id = e.faculty_id
    GROUP BY f.id, f.faculty_id, f.firstname, f.lastname, f.suffix, f.email
    ORDER BY average_score DESC";
    
    $result = $conn->query($query);
    
    $evaluations = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $average_score = $row['average_score'] ? round($row['average_score'], 2) : 0;
            $rating = getRatingLabel($average_score);
            
            $evaluations[] = [
                'id' => $row['id'],
                'faculty_id' => $row['faculty_id'],
                'name' => trim($row['firstname'] . ' ' . $row['lastname'] . ' ' . $row['suffix']),
                'email' => $row['email'],
                'average_score' => $average_score,
                'rating' => $rating,
                'rating_class' => getRatingClass($average_score),
                'total_responses' => $row['total_responses'] ?? 0
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $evaluations
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching evaluations: ' . $e->getMessage()
    ]);
}

function getRatingLabel($score) {
    if ($score >= 4.5) return 'Excellent';
    if ($score >= 3.5) return 'Very Good';
    if ($score >= 2.5) return 'Good';
    if ($score >= 1.5) return 'Fair';
    return 'Poor';
}

function getRatingClass($score) {
    if ($score >= 4.5) return 'excellent';
    if ($score >= 3.5) return 'very-good';
    if ($score >= 2.5) return 'good';
    if ($score >= 1.5) return 'fair';
    return 'poor';
}

$conn->close();
?>

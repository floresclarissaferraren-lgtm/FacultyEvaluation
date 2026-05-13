<?php
header("Content-Type: application/json");
include 'connect.php';

try {
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
    if ($score == 0) return 'N/A';
    if ($score >= 4.5) return 'Outstanding';
    if ($score >= 3.5) return 'Very Good';
    if ($score >= 2.5) return 'Good';
    if ($score >= 1.5) return 'Fair';
    return 'Poor';
}

function getRatingClass($score) {
    if ($score == 0) return 'na';
    if ($score >= 4.5) return 'outstanding';
    if ($score >= 3.5) return 'very-good';
    if ($score >= 2.5) return 'good';
    if ($score >= 1.5) return 'fair';
    return 'poor';
}

$conn->close();
?>

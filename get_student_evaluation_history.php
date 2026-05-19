<?php
session_start();
header("Content-Type: application/json");
include 'connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$student_id = intval($_SESSION['id']);

try {
    $query = "SELECT 
        e.id as evaluation_id,
        e.overall_rating,
        e.feedback,
        e.created_at,
        f.id as faculty_id,
        f.faculty_id,
        f.firstname,
        f.lastname,
        f.suffix
    FROM evaluations e
    INNER JOIN add_faculties f ON e.faculty_id = f.id
    WHERE e.student_id = ?
    ORDER BY e.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    
    while ($row = $result->fetch_assoc()) {
        $faculty_name = trim($row['firstname'] . ' ' . $row['lastname'] . ' ' . $row['suffix']);
        $rating_label = getRatingLabel($row['overall_rating']);
        
        $history[] = [
            'evaluation_id' => $row['evaluation_id'],
            'faculty_id' => $row['faculty_id'],
            'faculty_name' => $faculty_name,
            'overall_rating' => $row['overall_rating'],
            'rating_label' => $rating_label,
            'feedback' => $row['feedback'],
            'date_evaluated' => date('F j, Y g:i A', strtotime($row['created_at']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $history
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching history: ' . $e->getMessage()
    ]);
}

function getRatingLabel($score) {
    if ($score >= 4.5) return 'Outstanding';
    if ($score >= 3.5) return 'Very Good';
    if ($score >= 2.5) return 'Good';
    if ($score >= 1.5) return 'Fair';
    return 'Poor';
}

if (isset($stmt)) $stmt->close();
if (isset($conn)) $conn->close();
?>
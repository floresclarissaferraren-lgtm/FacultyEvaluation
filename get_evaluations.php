<?php
header("Content-Type: application/json");
include 'connect.php';
require_once 'weighted_score_helper.php';

try {
    // Get faculty list with total responses
    $query = "SELECT 
        f.id,
        f.faculty_id,
        f.firstname,
        f.lastname,
        f.suffix,
        f.email,
        COUNT(e.id) as total_responses,
        MAX(e.created_at) as last_evaluation
    FROM add_faculties f
    LEFT JOIN evaluations e ON f.id = e.faculty_id
    GROUP BY f.id, f.faculty_id, f.firstname, f.lastname, f.suffix, f.email
    ORDER BY f.lastname ASC";

    $result = $conn->query($query);

    $evaluations = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $fid           = intval($row['id']);
            $total         = intval($row['total_responses']);
            // Real-time weighted score from stored answers
            $average_score = $total > 0 ? calcWeightedScore($conn, $fid) : 0.00;
            $rating        = getRatingLabel($average_score);

            $evaluations[] = [
                'id'               => $fid,
                'faculty_id'       => $row['faculty_id'],
                'name'             => trim($row['firstname'] . ' ' . $row['lastname'] . ' ' . $row['suffix']),
                'email'            => $row['email'],
                'average_score'    => $average_score,
                'percentage_score' => $total > 0 ? round(($average_score / 5) * 100, 2) : 0,
                'rating'           => $rating,
                'rating_class'     => getRatingClass($average_score),
                'total_responses'  => $total,
            ];
        }
    }

    // Sort by weighted score descending
    usort($evaluations, fn($a, $b) => $b['average_score'] <=> $a['average_score']);

    echo json_encode([
        'success' => true,
        'data'    => $evaluations
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

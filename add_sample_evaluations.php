<?php
include 'connect.php';

echo "<h2>Adding Sample Evaluation Data</h2>";

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

// Get first faculty member
$faculty_query = "SELECT id FROM add_faculties LIMIT 1";
$faculty_result = $conn->query($faculty_query);

if ($faculty_result && $faculty_result->num_rows > 0) {
    $faculty = $faculty_result->fetch_assoc();
    $faculty_id = $faculty['id'];
    
    echo "<p>Adding evaluations for Faculty ID: $faculty_id</p>";
    
    // Add sample evaluation data
    $sample_evaluations = [
        ['student_id' => 1, 'overall_rating' => 4.5, 'feedback' => 'Outstanding teaching!'],
        ['student_id' => 2, 'overall_rating' => 4.2, 'feedback' => 'Very good instructor'],
        ['student_id' => 3, 'overall_rating' => 3.8, 'feedback' => 'Good teaching style'],
        ['student_id' => 4, 'overall_rating' => 4.7, 'feedback' => 'Outstanding professor'],
        ['student_id' => 5, 'overall_rating' => 3.9, 'feedback' => 'Effective teaching methods']
    ];
    
    foreach ($sample_evaluations as $eval) {
        // Check if evaluation already exists
        $check_query = "SELECT id FROM evaluations WHERE student_id = ? AND faculty_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $eval['student_id'], $faculty_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            // Insert new evaluation
            $insert_query = "INSERT INTO evaluations (student_id, faculty_id, overall_rating, feedback) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iids", $eval['student_id'], $faculty_id, $eval['overall_rating'], $eval['feedback']);
            
            if ($insert_stmt->execute()) {
                echo "<p>✅ Added evaluation for student {$eval['student_id']} with rating {$eval['overall_rating']}</p>";
            } else {
                echo "<p>❌ Error adding evaluation: " . $conn->error . "</p>";
            }
        } else {
            echo "<p>⚠️ Evaluation already exists for student {$eval['student_id']}</p>";
        }
    }
    
    // Calculate and display summary
    $summary_query = "SELECT COUNT(*) as total_responses, AVG(overall_rating) as avg_rating FROM evaluations WHERE faculty_id = ?";
    $summary_stmt = $conn->prepare($summary_query);
    $summary_stmt->bind_param("i", $faculty_id);
    $summary_stmt->execute();
    $summary_result = $summary_stmt->get_result();
    $summary = $summary_result->fetch_assoc();
    
    echo "<h3>Summary for Faculty ID $faculty_id:</h3>";
    echo "<p>Total Responses: " . $summary['total_responses'] . "</p>";
    echo "<p>Average Rating: " . number_format($summary['avg_rating'], 2) . "</p>";
    
    // Determine status
    $avg_rating = floatval($summary['avg_rating']);
    if ($avg_rating >= 4.5) $status = 'Outstanding';
    elseif ($avg_rating >= 3.5) $status = 'Very Good';
    elseif ($avg_rating >= 2.5) $status = 'Good';
    elseif ($avg_rating >= 1.5) $status = 'Fair';
    else $status = 'Poor';
    
    echo "<p>Status: <strong>$status</strong></p>";
    
} else {
    echo "<p>No faculty found in database. Please add faculty first.</p>";
}

$conn->close();
?>

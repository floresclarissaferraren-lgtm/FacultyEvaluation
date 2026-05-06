<?php
// Test file to verify rating display logic
include "connect.php";

echo "<h2>Rating Display Test</h2>";

// Test the rating logic directly
function ratingLabel(float $score, int $total): string {
    // If no responses, return "No Rating Yet"
    if ($total === 0) {
        return "No Rating Yet";
    }
    
    if ($score >= 4.5) return "Outstanding";
    if ($score >= 3.5) return "Very Good";
    if ($score >= 2.5) return "Good";
    if ($score >= 1.5) return "Fair";
    return "Needs Improvement";
}

// Test cases
$test_cases = [
    ['score' => 0.00, 'total' => 0, 'expected' => 'No Rating Yet'],
    ['score' => 4.5, 'total' => 5, 'expected' => 'Outstanding'],
    ['score' => 3.8, 'total' => 3, 'expected' => 'Very Good'],
    ['score' => 2.8, 'total' => 2, 'expected' => 'Good'],
    ['score' => 1.8, 'total' => 1, 'expected' => 'Fair'],
    ['score' => 1.2, 'total' => 1, 'expected' => 'Needs Improvement'],
];

echo "<h3>Test Cases:</h3>";
foreach ($test_cases as $i => $test) {
    $result = ratingLabel($test['score'], $test['total']);
    $status = $result === $test['expected'] ? "✅ PASS" : "❌ FAIL";
    echo "<p>Test " . ($i + 1) . ": Score={$test['score']}, Total={$test['total']} -> $result (Expected: {$test['expected']}) $status</p>";
}

// Test actual database data
echo "<h3>Database Test:</h3>";

// Simulate faculty stats query
$faculty_id = 1; // Test with faculty ID 1
$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_responses,
        ROUND(AVG(overall_rating), 2) AS overall_rating
    FROM evaluations
    WHERE faculty_id = ?
");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

$overall = $row && $row['overall_rating'] !== null ? floatval($row['overall_rating']) : 0.00;
$total = $row ? intval($row['total_responses']) : 0;

$rating_label = ratingLabel($overall, $total);
$display_rating = $total === 0 ? "0.00" : number_format($overall, 2);

echo "<p>Faculty ID $faculty_id Results:</p>";
echo "<p>Total Responses: $total</p>";
echo "<p>Overall Rating: $display_rating</p>";
echo "<p>Rating Label: $rating_label</p>";

if ($total === 0) {
    echo "<p><strong>Display: No Rating Yet</strong> ✅</p>";
} else {
    echo "<p><strong>Display: $display_rating / 5.00 - $rating_label</strong> ✅</p>";
}

$conn->close();
?>

<?php
// Include database connection
require_once 'connect.php';
require_once 'evaluation_period_helper.php';

// Ensure tables exist
ensureEvaluationPeriodTables($conn);

// Get current evaluation settings
$query = "
    SELECT 
        es.evaluation_open,
        es.active_period_id,
        es.selected_ay,
        es.selected_semester,
        es.display_ay,
        es.display_semester,
        ep.ay,
        ep.semester,
        ep.start_date,
        ep.end_date,
        ep.is_active
    FROM evaluation_settings es
    LEFT JOIN evaluation_periods ep ON ep.id = es.active_period_id
    WHERE es.id = 1
";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    
    echo "<h2>Evaluation Period Information</h2>";
    echo "<table border='1' cellpadding='10' cellspacing='0'>";
    
    // Evaluation Status
    echo "<tr>";
    echo "<td><strong>Evaluation Status:</strong></td>";
    echo "<td>" . ($data['evaluation_open'] ? 'OPEN' : 'CLOSED') . "</td>";
    echo "</tr>";
    
    // Active Period Info
    if ($data['active_period_id']) {
        echo "<tr>";
        echo "<td><strong>Active Period ID:</strong></td>";
        echo "<td>" . htmlspecialchars($data['active_period_id']) . "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td><strong>Academic Year:</strong></td>";
        echo "<td>" . htmlspecialchars($data['ay'] ?? 'N/A') . "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td><strong>Semester:</strong></td>";
        echo "<td>" . htmlspecialchars($data['semester'] ?? 'N/A') . "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td><strong>Start Date:</strong></td>";
        echo "<td>" . htmlspecialchars($data['start_date'] ?? 'N/A') . "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td><strong>End Date:</strong></td>";
        echo "<td>" . htmlspecialchars($data['end_date'] ?? 'N/A') . "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td><strong>Is Active:</strong></td>";
        echo "<td>" . ($data['is_active'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td colspan='2'><em>No active evaluation period</em></td>";
        echo "</tr>";
    }
    
    // Display Settings
    if ($data['display_ay'] || $data['display_semester']) {
        echo "<tr>";
        echo "<td><strong>Display Academic Year:</strong></td>";
        echo "<td>" . htmlspecialchars($data['display_ay'] ?? 'N/A') . "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td><strong>Display Semester:</strong></td>";
        echo "<td>" . htmlspecialchars($data['display_semester'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // JSON Output
    echo "<h3>JSON Format:</h3>";
    echo "<pre>";
    echo json_encode($data, JSON_PRETTY_PRINT);
    echo "</pre>";
    
} else {
    echo "<p>No evaluation settings found in the database.</p>";
}

// Get all evaluation periods
echo "<h2>All Evaluation Periods</h2>";
$allPeriodsQuery = "SELECT * FROM evaluation_periods ORDER BY created_at DESC";
$allPeriodsResult = $conn->query($allPeriodsQuery);

if ($allPeriodsResult && $allPeriodsResult->num_rows > 0) {
    echo "<table border='1' cellpadding='10' cellspacing='0'>";
    echo "<tr>";
    echo "<th>ID</th>";
    echo "<th>Academic Year</th>";
    echo "<th>Semester</th>";
    echo "<th>Start Date</th>";
    echo "<th>End Date</th>";
    echo "<th>Is Active</th>";
    echo "<th>Created At</th>";
    echo "</tr>";
    
    while ($period = $allPeriodsResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($period['id']) . "</td>";
        echo "<td>" . htmlspecialchars($period['ay']) . "</td>";
        echo "<td>" . htmlspecialchars($period['semester']) . "</td>";
        echo "<td>" . htmlspecialchars($period['start_date']) . "</td>";
        echo "<td>" . htmlspecialchars($period['end_date']) . "</td>";
        echo "<td>" . ($period['is_active'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . htmlspecialchars($period['created_at']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No evaluation periods found in the database.</p>";
}

$conn->close();
?>

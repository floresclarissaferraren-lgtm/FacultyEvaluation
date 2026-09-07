<?php
// Temporary diagnostic file - delete after fixing
session_start();
include 'connect.php';

$faculty_id = $_SESSION['id'] ?? 'No ID in session';

echo "<h2>Faculty Data Diagnostic</h2>";
echo "<p><strong>Session ID:</strong> " . htmlspecialchars($faculty_id) . "</p>";

if (is_numeric($faculty_id)) {
    $stmt = $conn->prepare("SELECT * FROM add_faculties WHERE id = ?");
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<p><strong>Rows found:</strong> " . $result->num_rows . "</p>";
    
    if ($result->num_rows > 0) {
        $faculty = $result->fetch_assoc();
        echo "<h3>Faculty Record:</h3>";
        echo "<table border='1' cellpadding='10'>";
        foreach ($faculty as $key => $value) {
            $display_value = ($key === 'photo') ? '(photo data)' : htmlspecialchars($value ?? 'NULL');
            echo "<tr><td><strong>$key</strong></td><td>$display_value</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>No faculty record found with ID: $faculty_id</p>";
        
        // Show all faculty records to help identify the issue
        $all = $conn->query("SELECT id, faculty_id, firstname, lastname, email FROM add_faculties ORDER BY id DESC LIMIT 10");
        echo "<h3>Recent Faculty Records (last 10):</h3>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Faculty ID</th><th>First Name</th><th>Last Name</th><th>Email</th></tr>";
        while ($row = $all->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['faculty_id'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['firstname'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['lastname'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['email'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    $stmt->close();
}

$conn->close();
?>

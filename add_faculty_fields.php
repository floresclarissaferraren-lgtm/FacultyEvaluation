<?php
include "connect.php";

echo "<h3>Adding Program and Year Level fields to Faculty Table</h3>";

// Add program field
$sql_add_program = "ALTER TABLE add_faculties ADD COLUMN program VARCHAR(255) NULL AFTER suffix";
if ($conn->query($sql_add_program)) {
    echo "<p style='color: green;'>✓ Program field added successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error adding program field: " . $conn->error . "</p>";
}

// Add yearlevel field  
$sql_add_yearlevel = "ALTER TABLE add_faculties ADD COLUMN yearlevel VARCHAR(50) NULL AFTER program";
if ($conn->query($sql_add_yearlevel)) {
    echo "<p style='color: green;'>✓ Year Level field added successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error adding year level field: " . $conn->error . "</p>";
}

echo "<p><a href='FacultyAdmin.php'>Back to Admin Panel</a></p>";

$conn->close();
?>

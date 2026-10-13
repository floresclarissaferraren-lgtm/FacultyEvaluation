<?php
function ensureStudentTypeColumn(mysqli $conn): void {
    $check = $conn->query("SHOW COLUMNS FROM add_students LIKE 'student_type'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE add_students ADD COLUMN student_type VARCHAR(20) DEFAULT 'regular' AFTER section");
    }
}

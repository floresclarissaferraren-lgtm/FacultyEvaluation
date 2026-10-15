<?php
function ensureClassPeriodColumn(mysqli $conn): void
{
    $table = $conn->query("SHOW TABLES LIKE 'add_classes'");
    if (!$table || $table->num_rows === 0) {
        return;
    }

    $column = $conn->query("SHOW COLUMNS FROM add_classes LIKE 'period_id'");
    if ($column && $column->num_rows === 0) {
        $conn->query(
            "ALTER TABLE add_classes ADD COLUMN period_id INT(11) NULL AFTER semester",
        );
    }

    $index = $conn->query(
        "SHOW INDEX FROM add_classes WHERE Key_name = 'idx_add_classes_period'",
    );
    if ($index && $index->num_rows === 0) {
        $conn->query(
            "ALTER TABLE add_classes ADD KEY idx_add_classes_period (period_id)",
        );
    }
}

function ensureStudentSubjectClassesSchema(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS student_subject_classes (
        id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        student_id int(11) NOT NULL,
        subject_id int(11) NOT NULL,
        class_id int(11) NOT NULL,
        UNIQUE KEY unique_student_subject_class (student_id, subject_id, class_id),
        KEY idx_ssc_student (student_id),
        KEY idx_ssc_class (class_id)
    )");

    $index = $conn->query(
        "SHOW INDEX FROM student_subject_classes WHERE Key_name = 'unique_student_subject_class'",
    );
    if (!$index) {
        return;
    }

    $columns = [];
    while ($row = $index->fetch_assoc()) {
        $columns[intval($row["Seq_in_index"])] = $row["Column_name"];
    }
    ksort($columns);

    if (array_values($columns) !== ["student_id", "subject_id", "class_id"]) {
        if (!empty($columns)) {
            $conn->query(
                "ALTER TABLE student_subject_classes DROP INDEX unique_student_subject_class",
            );
        }
        $conn->query(
            "ALTER TABLE student_subject_classes ADD UNIQUE KEY unique_student_subject_class (student_id, subject_id, class_id)",
        );
    }
}
?>

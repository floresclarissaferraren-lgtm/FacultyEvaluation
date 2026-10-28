<?php
function ensureClassPeriodColumn(mysqli $conn): void
{
    $table = $conn->query("SHOW TABLES LIKE 'add_classes'");
    if (!$table || $table->num_rows === 0) {
        return;
    }

    $semesterColumn = $conn->query(
        "SHOW COLUMNS FROM add_classes LIKE 'semester'",
    );
    if ($semesterColumn && $semesterColumn->num_rows === 0) {
        $conn->query(
            "ALTER TABLE add_classes ADD COLUMN semester VARCHAR(20) NOT NULL DEFAULT '1st Semester' AFTER year_level",
        );
    }

    $column = $conn->query("SHOW COLUMNS FROM add_classes LIKE 'period_id'");
    if (!$column || $column->num_rows === 0) {
        return;
    }

    // Preserve the semester on each class before removing its academic-year link.
    $periodTable = $conn->query("SHOW TABLES LIKE 'evaluation_periods'");
    if ($periodTable && $periodTable->num_rows > 0) {
        $conn->query("UPDATE add_classes ac
            INNER JOIN evaluation_periods ep ON ep.id = ac.period_id
            SET ac.semester = ep.semester");
    }

    $foreignKeys = $conn->query("SELECT DISTINCT CONSTRAINT_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'add_classes'
          AND COLUMN_NAME = 'period_id'
          AND REFERENCED_TABLE_NAME IS NOT NULL");
    if ($foreignKeys) {
        while ($foreignKey = $foreignKeys->fetch_assoc()) {
            $name = str_replace(
                '`',
                '``',
                (string) $foreignKey['CONSTRAINT_NAME'],
            );
            $conn->query("ALTER TABLE add_classes DROP FOREIGN KEY `{$name}`");
        }
    }

    $indexes = $conn->query(
        "SHOW INDEX FROM add_classes WHERE Column_name = 'period_id'",
    );
    $indexNames = [];
    if ($indexes) {
        while ($index = $indexes->fetch_assoc()) {
            $name = (string) $index['Key_name'];
            if ($name !== 'PRIMARY') {
                $indexNames[$name] = true;
            }
        }
    }
    foreach (array_keys($indexNames) as $name) {
        $escapedName = str_replace('`', '``', $name);
        $conn->query("ALTER TABLE add_classes DROP INDEX `{$escapedName}`");
    }

    $conn->query("ALTER TABLE add_classes DROP COLUMN period_id");
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

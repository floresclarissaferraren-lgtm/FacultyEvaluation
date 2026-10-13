<?php
function ensureEvaluationsSchema(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS evaluations (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            student_id INT(11) NOT NULL,
            faculty_id INT(11) NOT NULL,
            subject_id INT(11) NOT NULL DEFAULT 0,
            class_id INT(11) NOT NULL DEFAULT 0,
            overall_rating DECIMAL(4,2) NOT NULL DEFAULT 0.00,
            feedback TEXT NULL,
            period_id INT(11) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_student_faculty_subject_class_period (student_id, faculty_id, subject_id, class_id, period_id),
            KEY idx_eval_faculty (faculty_id),
            KEY idx_eval_student (student_id),
            KEY idx_eval_subject (subject_id),
            KEY idx_eval_class (class_id),
            KEY idx_eval_period (period_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    ensureEvaluationColumn($conn, 'subject_id', "ALTER TABLE evaluations ADD COLUMN subject_id INT(11) NOT NULL DEFAULT 0 AFTER faculty_id");
    ensureEvaluationColumn($conn, 'class_id', "ALTER TABLE evaluations ADD COLUMN class_id INT(11) NOT NULL DEFAULT 0 AFTER subject_id");
    ensureEvaluationColumn($conn, 'period_id', "ALTER TABLE evaluations ADD COLUMN period_id INT(11) NULL AFTER feedback");

    dropEvaluationIndexIfExists($conn, 'uniq_student_faculty');
    dropEvaluationIndexIfExists($conn, 'uniq_student_faculty_subject_class');
    addEvaluationIndexIfMissing($conn, 'idx_eval_faculty', "ALTER TABLE evaluations ADD KEY idx_eval_faculty (faculty_id)");
    addEvaluationIndexIfMissing($conn, 'idx_eval_student', "ALTER TABLE evaluations ADD KEY idx_eval_student (student_id)");
    addEvaluationIndexIfMissing($conn, 'idx_eval_subject', "ALTER TABLE evaluations ADD KEY idx_eval_subject (subject_id)");
    addEvaluationIndexIfMissing($conn, 'idx_eval_class', "ALTER TABLE evaluations ADD KEY idx_eval_class (class_id)");
    addEvaluationIndexIfMissing($conn, 'idx_eval_period', "ALTER TABLE evaluations ADD KEY idx_eval_period (period_id)");
    addEvaluationIndexIfMissing($conn, 'uniq_student_faculty_subject_class_period', "ALTER TABLE evaluations ADD UNIQUE KEY uniq_student_faculty_subject_class_period (student_id, faculty_id, subject_id, class_id, period_id)");
}

function ensureEvaluationColumn(mysqli $conn, string $column, string $alterSql): void
{
    $safeColumn = $conn->real_escape_string($column);
    $check = $conn->query("SHOW COLUMNS FROM evaluations LIKE '{$safeColumn}'");
    if ($check && $check->num_rows === 0) {
        $conn->query($alterSql);
    }
}

function addEvaluationIndexIfMissing(mysqli $conn, string $indexName, string $alterSql): void
{
    $safeIndex = $conn->real_escape_string($indexName);
    $check = $conn->query("SHOW INDEX FROM evaluations WHERE Key_name = '{$safeIndex}'");
    if ($check && $check->num_rows === 0) {
        $conn->query($alterSql);
    }
}

function dropEvaluationIndexIfExists(mysqli $conn, string $indexName): void
{
    $safeIndex = $conn->real_escape_string($indexName);
    $check = $conn->query("SHOW INDEX FROM evaluations WHERE Key_name = '{$safeIndex}'");
    if ($check && $check->num_rows > 0) {
        $conn->query("ALTER TABLE evaluations DROP INDEX {$indexName}");
    }
}
?>

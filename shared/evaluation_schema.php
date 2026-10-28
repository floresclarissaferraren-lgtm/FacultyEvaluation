<?php
function ensureEvaluationsSchema(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS evaluations (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        student_id INT(11) NOT NULL,
        faculty_id INT(11) NOT NULL,
        subject_id INT(11) NOT NULL DEFAULT 0,
        class_id INT(11) NOT NULL DEFAULT 0,
        overall_rating DECIMAL(4,2) NOT NULL DEFAULT 0.00,
        feedback TEXT NULL,
        academic_year VARCHAR(20) NOT NULL DEFAULT '',
        semester VARCHAR(50) NOT NULL DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_student_faculty_subject_class_term (student_id, faculty_id, subject_id, class_id, academic_year, semester),
        KEY idx_eval_faculty (faculty_id),
        KEY idx_eval_student (student_id),
        KEY idx_eval_subject (subject_id),
        KEY idx_eval_class (class_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    ensureEvaluationColumn($conn, "subject_id", "ALTER TABLE evaluations ADD COLUMN subject_id INT(11) NOT NULL DEFAULT 0 AFTER faculty_id");
    ensureEvaluationColumn($conn, "class_id", "ALTER TABLE evaluations ADD COLUMN class_id INT(11) NOT NULL DEFAULT 0 AFTER subject_id");
    ensureEvaluationColumn($conn, "academic_year", "ALTER TABLE evaluations ADD COLUMN academic_year VARCHAR(20) NOT NULL DEFAULT '' AFTER feedback");
    ensureEvaluationColumn($conn, "semester", "ALTER TABLE evaluations ADD COLUMN semester VARCHAR(50) NOT NULL DEFAULT '' AFTER academic_year");

    $hasPeriod = evaluationColumnExists($conn, "period_id");
    if ($hasPeriod) {
        $conn->query("UPDATE evaluations e
            INNER JOIN evaluation_periods ep ON ep.id = e.period_id
            SET e.academic_year = ep.ay, e.semester = ep.semester
            WHERE e.academic_year = '' OR e.semester = ''");
        $conn->query("UPDATE evaluations e
            INNER JOIN evaluation_periods ep ON DATE(e.created_at) BETWEEN ep.start_date AND ep.end_date
            SET e.academic_year = ep.ay, e.semester = ep.semester
            WHERE (e.academic_year = '' OR e.semester = '') AND e.period_id IS NULL");
    }

    if ($hasPeriod) {
        $foreignKeys = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluations'
              AND COLUMN_NAME = 'period_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
        if ($foreignKeys) {
            while ($fk = $foreignKeys->fetch_assoc()) {
                $name = str_replace('`', '', $fk['CONSTRAINT_NAME']);
                $conn->query("ALTER TABLE evaluations DROP FOREIGN KEY `{$name}`");
            }
        }
        $conn->query("ALTER TABLE evaluations DROP COLUMN period_id");
    }
    dropEvaluationIndexIfExists($conn, "uniq_student_faculty");
    dropEvaluationIndexIfExists($conn, "uniq_student_faculty_subject_class");
    if ($hasPeriod) {
        dropEvaluationIndexIfExists($conn, "uniq_student_faculty_subject_class_period");
        dropEvaluationIndexIfExists($conn, "idx_eval_period");
    }
    addEvaluationIndexIfMissing($conn, "idx_eval_faculty", "ALTER TABLE evaluations ADD KEY idx_eval_faculty (faculty_id)");
    addEvaluationIndexIfMissing($conn, "idx_eval_student", "ALTER TABLE evaluations ADD KEY idx_eval_student (student_id)");
    addEvaluationIndexIfMissing($conn, "idx_eval_subject", "ALTER TABLE evaluations ADD KEY idx_eval_subject (subject_id)");
    addEvaluationIndexIfMissing($conn, "idx_eval_class", "ALTER TABLE evaluations ADD KEY idx_eval_class (class_id)");
    addEvaluationIndexIfMissing($conn, "uniq_student_faculty_subject_class_term",
        "ALTER TABLE evaluations ADD UNIQUE KEY uniq_student_faculty_subject_class_term (student_id, faculty_id, subject_id, class_id, academic_year, semester)");
}

function evaluationColumnExists(mysqli $conn, string $column): bool
{
    $safe = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM evaluations LIKE '{$safe}'");
    return $result && $result->num_rows > 0;
}

function ensureEvaluationColumn(mysqli $conn, string $column, string $alterSql): void
{
    if (!evaluationColumnExists($conn, $column)) {
        $conn->query($alterSql);
    }
}

function addEvaluationIndexIfMissing(mysqli $conn, string $indexName, string $alterSql): void
{
    $safe = $conn->real_escape_string($indexName);
    $check = $conn->query("SHOW INDEX FROM evaluations WHERE Key_name = '{$safe}'");
    if ($check && $check->num_rows === 0) {
        $conn->query($alterSql);
    }
}

function dropEvaluationIndexIfExists(mysqli $conn, string $indexName): void
{
    $safe = $conn->real_escape_string($indexName);
    $check = $conn->query("SHOW INDEX FROM evaluations WHERE Key_name = '{$safe}'");
    if ($check && $check->num_rows > 0) {
        $conn->query("ALTER TABLE evaluations DROP INDEX `{$indexName}`");
    }
}
?>

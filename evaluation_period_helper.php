<?php
date_default_timezone_set("Asia/Manila");

if (!function_exists('evaluationPeriodTableColumnExists')) {
    function evaluationPeriodTableColumnExists(mysqli $conn, string $table, string $column): bool {
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS count
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->bind_param("ss", $table, $column);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return intval($row["count"] ?? 0) > 0;
    }
}

if (!function_exists('ensureEvaluationPeriodTables')) {
    function ensureEvaluationPeriodTables(mysqli $conn): void {
        $conn->query("
            CREATE TABLE IF NOT EXISTS evaluation_periods (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                ay VARCHAR(20) NOT NULL,
                semester VARCHAR(20) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $conn->query("
            CREATE TABLE IF NOT EXISTS evaluation_settings (
                id INT(11) NOT NULL PRIMARY KEY,
                evaluation_open TINYINT(1) NOT NULL DEFAULT 0,
                active_period_id INT(11) NULL,
                selected_ay VARCHAR(20) NULL,
                selected_semester VARCHAR(20) NULL,
                display_ay VARCHAR(20) NULL,
                display_semester VARCHAR(20) NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_active_period (active_period_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        if (!evaluationPeriodTableColumnExists($conn, "evaluation_settings", "selected_ay")) {
            $conn->query("ALTER TABLE evaluation_settings ADD COLUMN selected_ay VARCHAR(20) NULL AFTER active_period_id");
        }
        if (!evaluationPeriodTableColumnExists($conn, "evaluation_settings", "selected_semester")) {
            $conn->query("ALTER TABLE evaluation_settings ADD COLUMN selected_semester VARCHAR(20) NULL AFTER selected_ay");
        }
        if (!evaluationPeriodTableColumnExists($conn, "evaluation_settings", "display_ay")) {
            $conn->query("ALTER TABLE evaluation_settings ADD COLUMN display_ay VARCHAR(20) NULL AFTER selected_semester");
        }
        if (!evaluationPeriodTableColumnExists($conn, "evaluation_settings", "display_semester")) {
            $conn->query("ALTER TABLE evaluation_settings ADD COLUMN display_semester VARCHAR(20) NULL AFTER display_ay");
        }

        $conn->query("INSERT IGNORE INTO evaluation_settings (id, evaluation_open, active_period_id) VALUES (1, 0, NULL)");
    }
}

if (!function_exists('isEvaluationOngoing')) {
    function isEvaluationOngoing(mysqli $conn): bool {
        ensureEvaluationPeriodTables($conn);
        closeExpiredEvaluationPeriod($conn);
        $today = date("Y-m-d");

        $stmt = $conn->prepare("
            SELECT ep.id
            FROM evaluation_settings es
            INNER JOIN evaluation_periods ep ON ep.id = es.active_period_id
            WHERE es.id = 1
              AND es.evaluation_open = 1
              AND es.active_period_id IS NOT NULL
              AND ep.start_date <= ?
              AND ep.end_date >= ?
            LIMIT 1
        ");
        $stmt->bind_param("ss", $today, $today);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (bool)$row;
    }
}

if (!function_exists('closeExpiredEvaluationPeriod')) {
    function closeExpiredEvaluationPeriod(mysqli $conn): void {
        ensureEvaluationPeriodTables($conn);
        $today = date("Y-m-d");

        $stmt = $conn->prepare("
            SELECT es.active_period_id
            FROM evaluation_settings es
            INNER JOIN evaluation_periods ep ON ep.id = es.active_period_id
            WHERE es.id = 1
              AND es.active_period_id IS NOT NULL
              AND ep.end_date < ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $expired = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($expired) {
            $conn->begin_transaction();
            try {
                $conn->query("UPDATE evaluation_periods SET is_active = 0");
                $conn->query("UPDATE evaluation_settings SET evaluation_open = 0, active_period_id = NULL WHERE id = 1");
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
        }
    }
}

if (!function_exists('blockFacultyResultsWhileEvaluationOngoing')) {
    function blockFacultyResultsWhileEvaluationOngoing(mysqli $conn): void {
        if (isEvaluationOngoing($conn)) {
            echo json_encode([
                "success" => false,
                "evaluation_ongoing" => true,
                "message" => "Evaluation results are unavailable while the evaluation process is still ongoing."
            ]);
            $conn->close();
            exit;
        }
    }
}
?>

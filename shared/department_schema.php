<?php
function ensureDepartmentSchema(mysqli $conn): void
{
    $createDepartments = "
        CREATE TABLE IF NOT EXISTS add_departments (
            id INT NOT NULL AUTO_INCREMENT,
            department_code VARCHAR(20) NOT NULL,
            department_name VARCHAR(120) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY department_code (department_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";
    $conn->query($createDepartments);

    $columns = $conn->query("SHOW COLUMNS FROM add_programs LIKE 'department_id'");
    if ($columns->num_rows === 0) {
        $conn->query(
            "ALTER TABLE add_programs ADD COLUMN department_id INT DEFAULT NULL AFTER id",
        );
    }

    $indexes = $conn->query(
        "SHOW INDEX FROM add_programs WHERE Key_name = 'idx_add_programs_department_id'",
    );
    if ($indexes->num_rows === 0) {
        $conn->query(
            "ALTER TABLE add_programs ADD INDEX idx_add_programs_department_id (department_id)",
        );
    }

    $foreignKeys = $conn->query("
        SELECT CONSTRAINT_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'add_programs'
          AND COLUMN_NAME = 'department_id'
          AND REFERENCED_TABLE_NAME = 'add_departments'
        LIMIT 1
    ");
    if ($foreignKeys->num_rows === 0) {
        $conn->query("
            ALTER TABLE add_programs
            ADD CONSTRAINT add_programs_ibfk_department
            FOREIGN KEY (department_id) REFERENCES add_departments (id)
            ON DELETE RESTRICT
        ");
    }
}

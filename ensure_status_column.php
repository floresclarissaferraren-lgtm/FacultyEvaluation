<?php
function ensureStatusColumn(mysqli $conn, string $table): void {
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $check = $conn->query("SHOW COLUMNS FROM {$safeTable} LIKE 'status'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE {$safeTable} ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'");
    }
}

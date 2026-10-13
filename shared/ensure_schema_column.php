<?php
function ensureColumnExists(mysqli $conn, string $table, string $column, string $definition, ?string $afterColumn = null): void {
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    $safeDefinition = trim($definition);
    $safeAfterColumn = $afterColumn !== null ? preg_replace('/[^a-zA-Z0-9_]/', '', $afterColumn) : '';

    $check = $conn->query("SHOW COLUMNS FROM {$safeTable} LIKE '{$safeColumn}'");
    if ($check && $check->num_rows === 0) {
        $sql = "ALTER TABLE {$safeTable} ADD COLUMN {$safeColumn} {$safeDefinition}";
        if ($safeAfterColumn !== '') {
            $sql .= " AFTER {$safeAfterColumn}";
        }
        $conn->query($sql);
    }
}

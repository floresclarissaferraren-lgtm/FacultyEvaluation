<?php
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include 'connect.php';

function scalarCount(mysqli $conn, string $sql): int {
    $result = $conn->query($sql);
    if (!$result) {
        return 0;
    }
    $row = $result->fetch_assoc();
    return isset($row['total']) ? (int)$row['total'] : 0;
}

function ensureStatusColumn(mysqli $conn, string $table): void {
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $check = $conn->query("SHOW COLUMNS FROM {$safeTable} LIKE 'status'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE {$safeTable} ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'");
    }
}

ensureStatusColumn($conn, "add_faculties");
ensureStatusColumn($conn, "add_students");

$stats = [
    "faculty" => [
        "total_faculty" => scalarCount($conn, "SELECT COUNT(*) AS total FROM add_faculties"),
        "active_faculty" => scalarCount($conn, "SELECT COUNT(*) AS total FROM add_faculties WHERE LOWER(TRIM(status)) = 'active'"),
        "pending_evaluation" => scalarCount($conn, "
            SELECT COUNT(DISTINCT f.id) AS total
            FROM add_faculties f
            LEFT JOIN evaluations e ON f.id = e.faculty_id
            WHERE e.faculty_id IS NULL
              AND LOWER(TRIM(COALESCE(f.status, 'active'))) = 'active'
        ")
    ],
    "students" => [
        "total_students" => scalarCount($conn, "SELECT COUNT(*) AS total FROM add_students"),
        "active_students" => scalarCount($conn, "SELECT COUNT(*) AS total FROM add_students WHERE LOWER(TRIM(status)) = 'active'"),
        "regular_students" => scalarCount($conn, "SELECT COUNT(*) AS total FROM add_students WHERE student_type = 'regular'"),
        "irregular_students" => scalarCount($conn, "SELECT COUNT(*) AS total FROM add_students WHERE student_type = 'irregular'")
    ]
];

echo json_encode(["success" => true, "data" => $stats]);
$conn->close();
?>

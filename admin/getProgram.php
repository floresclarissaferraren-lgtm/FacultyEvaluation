<?php
require_once __DIR__ . "/../shared/security.php";
requireRole("admin");
include __DIR__ . "/../shared/connect.php";

header("Content-Type: application/json");

$query = "
    SELECT p.id, p.program_code, p.program_name,
        (
            SELECT COUNT(*)
            FROM add_students st
            WHERE st.program = CAST(p.id AS CHAR)
               OR UPPER(TRIM(st.program)) = UPPER(TRIM(p.program_code))
               OR UPPER(TRIM(st.program)) = UPPER(TRIM(p.program_name))
        ) AS enrolled_students
    FROM add_programs p
    ORDER BY p.id DESC
";
$result = mysqli_query($conn, $query);

$programs = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $programs[] = $row;
    }
}

echo json_encode($programs);
exit();

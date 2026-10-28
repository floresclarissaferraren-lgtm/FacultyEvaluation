<?php
require_once __DIR__ . "/../shared/security.php";
requireRole("admin");
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include __DIR__ . "/../shared/connect.php";
require_once __DIR__ . "/../shared/evaluation_schema.php";

try {
    ensureEvaluationsSchema($conn);

    $periodResult = $conn->query(
        "SELECT active_period_id FROM evaluation_settings WHERE id = 1 LIMIT 1",
    );
    if (!$periodResult) {
        throw new Exception("Unable to load the active evaluation period.");
    }
    $periodRow = $periodResult->fetch_assoc() ?: [];
    $activePeriodId = intval($periodRow["active_period_id"] ?? 0);
    $progressPeriodId = $activePeriodId;

    // When evaluations are closed, keep showing the latest period with saved submissions.
    if ($progressPeriodId <= 0) {
        $savedPeriodResult = $conn->query("
            SELECT ep.id
            FROM evaluation_periods ep
            INNER JOIN evaluations e ON TRIM(e.academic_year) = TRIM(ep.ay)
                AND LOWER(TRIM(e.semester)) = LOWER(TRIM(ep.semester))
            GROUP BY ep.id
            ORDER BY MAX(e.created_at) DESC, ep.id DESC
            LIMIT 1
        ");
        if ($savedPeriodResult) {
            $savedPeriodRow = $savedPeriodResult->fetch_assoc() ?: [];
            $progressPeriodId = intval($savedPeriodRow["id"] ?? 0);
        }
    }

    $hasRecords = false;
    if ($progressPeriodId > 0) {
        $recordStmt = $conn->prepare(
            "SELECT 1 FROM evaluations e INNER JOIN evaluation_periods ep ON ep.id = ? AND TRIM(e.academic_year) = TRIM(ep.ay) AND LOWER(TRIM(e.semester)) = LOWER(TRIM(ep.semester)) LIMIT 1",
        );
        if ($recordStmt) {
            $recordStmt->bind_param("i", $progressPeriodId);
            $recordStmt->execute();
            $hasRecords = (bool) $recordStmt->get_result()->fetch_row();
            $recordStmt->close();
        }
    }

    $stmt = $conn->prepare("
        SELECT
            p.id,
            p.program_code,
            p.program_name,
            COALESCE(program_progress.expected_students, 0) AS expected_students,
            COALESCE(program_progress.completed_students, 0) AS completed_students
        FROM add_programs p
        LEFT JOIN (
            SELECT
                student_progress.program_id,
                COUNT(*) AS expected_students,
                COALESCE(SUM(student_progress.pending_assignments = 0), 0) AS completed_students
            FROM (
                SELECT
                    ac.program_id,
                    st.id AS student_id,
                    COUNT(DISTINCT CASE
                        WHEN ev.id IS NULL THEN CONCAT(cs.faculty_id, ':', cs.subject_id, ':', ac.id)
                    END) AS pending_assignments
                FROM add_students st
                INNER JOIN evaluation_periods ep ON ep.id = ?
                INNER JOIN add_classes ac
                    ON LOWER(TRIM(ac.semester)) = LOWER(TRIM(ep.semester))
                   AND (
                       CAST(ac.program_id AS CHAR) = TRIM(st.program)
                       OR EXISTS (
                           SELECT 1
                           FROM add_programs matched_program
                           WHERE matched_program.id = ac.program_id
                             AND (
                                 TRIM(matched_program.program_code) = TRIM(st.program)
                                 OR TRIM(matched_program.program_name) = TRIM(st.program)
                             )
                       )
                   )
                   AND (
                       ac.year_level = st.yearlevel
                       OR ac.year_level = CONCAT(
                           st.yearlevel,
                           CASE st.yearlevel
                               WHEN '1' THEN 'st Year'
                               WHEN '2' THEN 'nd Year'
                               WHEN '3' THEN 'rd Year'
                               ELSE 'th Year'
                           END
                       )
                   )
                   AND TRIM(UPPER(ac.block)) = TRIM(UPPER(st.section))
                INNER JOIN class_subjects cs ON cs.class_id = ac.id AND cs.faculty_id > 0
                INNER JOIN add_faculties f ON f.id = cs.faculty_id
                INNER JOIN add_subjects s
                    ON s.id = cs.subject_id
                   AND LOWER(TRIM(s.semester)) = LOWER(TRIM(ep.semester))
                LEFT JOIN evaluations ev
                    ON ev.student_id = st.id
                   AND ev.faculty_id = cs.faculty_id
                   AND ev.subject_id = cs.subject_id
                   AND ev.class_id = ac.id
                   AND TRIM(ev.academic_year) = TRIM(ep.ay)
                   AND LOWER(TRIM(ev.semester)) = LOWER(TRIM(ep.semester))
                WHERE LOWER(TRIM(COALESCE(st.status, 'active'))) = 'active'
                GROUP BY ac.program_id, st.id
            ) AS student_progress
            GROUP BY student_progress.program_id
        ) AS program_progress ON program_progress.program_id = p.id
        ORDER BY p.program_name
    ");
    if (!$stmt) {
        throw new Exception(
            "Unable to prepare program evaluation progress query.",
        );
    }

    $stmt->bind_param("i", $progressPeriodId);
    $stmt->execute();
    $result = $stmt->get_result();
    $programs = [];
    $totalExpected = 0;
    $totalCompleted = 0;

    while ($row = $result->fetch_assoc()) {
        $expected = intval($row["expected_students"] ?? 0);
        $completed = intval($row["completed_students"] ?? 0);
        $totalExpected += $expected;
        $totalCompleted += $completed;
        $programs[] = [
            "code" => (string) ($row["program_code"] ?? ""),
            "name" => (string) ($row["program_name"] ?? ""),
            "expected" => $expected,
            "completed" => $completed,
            "percentage" =>
                $expected > 0 ? round(($completed / $expected) * 100, 1) : 0,
        ];
    }

    $stmt->close();
    $pending = max($totalExpected - $totalCompleted, 0);
    $overallPercentage =
        $totalExpected > 0
            ? round(($totalCompleted / $totalExpected) * 100, 1)
            : 0;

    echo json_encode([
        "success" => true,
        "data" => [
            "activePeriodId" => $activePeriodId,
            "progressPeriodId" => $progressPeriodId,
            "hasRecords" => $hasRecords,
            "programs" => $programs,
            "completed" => $totalCompleted,
            "pending" => $pending,
            "overallPercentage" => $overallPercentage,
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to load program evaluation progress: " . $e->getMessage(),
    ]);
}

$conn->close();
?>

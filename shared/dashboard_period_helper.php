<?php
function getDashboardEvaluationPeriodId(mysqli $conn): int
{
    $settingsResult = $conn->query(
        "SELECT * FROM evaluation_settings WHERE id = 1 LIMIT 1",
    );
    $settings = $settingsResult ? $settingsResult->fetch_assoc() : null;
    if (!$settings) {
        return 0;
    }

    $activePeriodId = intval($settings["active_period_id"] ?? 0);
    if ($activePeriodId > 0) {
        return $activePeriodId;
    }

    $academicYear = trim(
        (string) (($settings["display_ay"] ?? "") ?: ($settings["selected_ay"] ?? "")),
    );
    $semester = trim(
        (string) (($settings["display_semester"] ?? "") ?: ($settings["selected_semester"] ?? "")),
    );
    if ($academicYear !== "" && $semester !== "") {
        $selectedStmt = $conn->prepare(
            "SELECT id FROM evaluation_periods
             WHERE TRIM(ay) = ? AND LOWER(TRIM(semester)) = LOWER(?)
             ORDER BY id DESC LIMIT 1",
        );
        if (!$selectedStmt) {
            return 0;
        }
        $selectedStmt->bind_param("ss", $academicYear, $semester);
        $selectedStmt->execute();
        $selected = $selectedStmt->get_result()->fetch_assoc();
        $selectedStmt->close();
        return intval($selected["id"] ?? 0);
    }

    $latestResult = $conn->query(
        "SELECT ep.id
         FROM evaluation_periods ep
         INNER JOIN evaluations e
           ON TRIM(e.academic_year) = TRIM(ep.ay)
          AND LOWER(TRIM(e.semester)) = LOWER(TRIM(ep.semester))
         GROUP BY ep.id
         ORDER BY MAX(e.created_at) DESC, ep.id DESC
         LIMIT 1",
    );
    $latest = $latestResult ? $latestResult->fetch_assoc() : null;
    return intval($latest["id"] ?? 0);
}
?>

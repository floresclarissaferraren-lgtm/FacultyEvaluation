<?php
session_start();
header("Content-Type: application/json");
include "connect.php";
date_default_timezone_set("Asia/Manila");

function ensurePeriodTables(mysqli $conn): void {
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

    if (!tableColumnExists($conn, "evaluation_settings", "selected_ay")) {
        $conn->query("ALTER TABLE evaluation_settings ADD COLUMN selected_ay VARCHAR(20) NULL AFTER active_period_id");
    }
    if (!tableColumnExists($conn, "evaluation_settings", "selected_semester")) {
        $conn->query("ALTER TABLE evaluation_settings ADD COLUMN selected_semester VARCHAR(20) NULL AFTER selected_ay");
    }
    if (!tableColumnExists($conn, "evaluation_settings", "display_ay")) {
        $conn->query("ALTER TABLE evaluation_settings ADD COLUMN display_ay VARCHAR(20) NULL AFTER selected_semester");
    }
    if (!tableColumnExists($conn, "evaluation_settings", "display_semester")) {
        $conn->query("ALTER TABLE evaluation_settings ADD COLUMN display_semester VARCHAR(20) NULL AFTER display_ay");
    }

    // Ensure singleton row exists.
    $conn->query("INSERT IGNORE INTO evaluation_settings (id, evaluation_open, active_period_id) VALUES (1, 0, NULL)");
}

function tableColumnExists(mysqli $conn, string $table, string $column): bool {
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

function requireAdmin(): void {
    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
        exit;
    }
}

function todayDate(): string {
    return date("Y-m-d");
}

function closeExpiredActivePeriod(mysqli $conn): void {
    $today = todayDate();
    $stmt = $conn->prepare("
        SELECT es.active_period_id
        FROM evaluation_settings es
        JOIN evaluation_periods ep ON ep.id = es.active_period_id
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
        $conn->query("UPDATE evaluation_periods SET is_active = 0");
        $conn->query("UPDATE evaluation_settings SET evaluation_open = 0, active_period_id = NULL WHERE id = 1");
    }
}

ensurePeriodTables($conn);
closeExpiredActivePeriod($conn);

$action = $_GET["action"] ?? "";

// Public-ish read used by other pages
if ($action === "status") {
    $settingsRes = $conn->query("
        SELECT evaluation_open, active_period_id, selected_ay, selected_semester, display_ay, display_semester
        FROM evaluation_settings
        WHERE id = 1
        LIMIT 1
    ");
    $settings = $settingsRes ? $settingsRes->fetch_assoc() : null;
    $evaluationOpen = $settings ? intval($settings["evaluation_open"]) === 1 : false;
    $activePeriodId = $settings ? intval($settings["active_period_id"] ?? 0) : 0;

    $activeName = null;
    $activeAcademicYear = null;
    $activeSemester = null;
    $currentAcademicYear = $settings ? (string)(($settings["display_ay"] ?? "") ?: ($settings["selected_ay"] ?? "")) : "";
    $currentSemester = $settings ? (string)(($settings["display_semester"] ?? "") ?: ($settings["selected_semester"] ?? "")) : "";
    $activeInDuration = false;
    if ($activePeriodId > 0) {
        $today = todayDate();
        $stmt = $conn->prepare("SELECT ay, semester, start_date, end_date FROM evaluation_periods WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $activePeriodId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $sem = (string)($row["semester"] ?? "");
            $activeAcademicYear = (string)($row["ay"] ?? "");
            $activeSemester = $sem;
            $currentAcademicYear = $activeAcademicYear;
            $currentSemester = $activeSemester;
            $prefix = $sem;
            if (stripos($sem, "1st") !== false) $prefix = "1st";
            else if (stripos($sem, "2nd") !== false) $prefix = "2nd";
            $activeName = trim($prefix . " " . $activeAcademicYear);
            $activeInDuration = ($row["start_date"] <= $today && $row["end_date"] >= $today);
        }
    }
    $evaluationOpen = $evaluationOpen && $activePeriodId > 0 && $activeInDuration;

    echo json_encode([
        "success" => true,
        "evaluation_open" => $evaluationOpen,
        "active_period_id" => $activePeriodId > 0 ? $activePeriodId : null,
        "active_period_name" => $activeName,
        "active_academic_year" => $activeAcademicYear,
        "active_semester" => $activeSemester,
        "current_academic_year" => $currentAcademicYear !== "" ? $currentAcademicYear : null,
        "current_semester" => $currentSemester !== "" ? $currentSemester : null
    ]);
    exit;
}

// Admin-only actions beyond this point.
requireAdmin();

if ($action === "list") {
    $res = $conn->query("SELECT id, ay, semester, start_date, end_date, is_active FROM evaluation_periods ORDER BY created_at DESC, id DESC");
    $periods = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $periods[] = [
                "id" => intval($row["id"]),
                "ay" => $row["ay"],
                "semester" => $row["semester"],
                "start_date" => $row["start_date"],
                "end_date" => $row["end_date"],
                "is_active" => intval($row["is_active"]) === 1
            ];
        }
    }
    echo json_encode(["success" => true, "periods" => $periods]);
    exit;
}

$payload = json_decode(file_get_contents("php://input"), true);
if (!is_array($payload)) $payload = [];

if ($action === "set_dashboard_period") {
    $academicYear = trim((string)($payload["academic_year"] ?? ""));
    $semester = trim((string)($payload["semester"] ?? ""));

    if ($academicYear !== "" && !preg_match('/^\d{4}-\d{4}$/', $academicYear)) {
        echo json_encode(["success" => false, "message" => "Invalid academic year"]);
        exit;
    }

    if ($semester !== "" && !in_array($semester, ["1st Semester", "2nd Semester", "Summer"], true)) {
        echo json_encode(["success" => false, "message" => "Invalid semester"]);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE evaluation_settings
        SET selected_ay = ?, selected_semester = ?, display_ay = ?, display_semester = ?
        WHERE id = 1
    ");
    $stmt->bind_param("ssss", $academicYear, $semester, $academicYear, $semester);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(["success" => $ok]);
    exit;
}

if ($action === "create") {
    $ay = trim((string)($payload["ay"] ?? ""));
    $semester = trim((string)($payload["semester"] ?? ""));
    $startDate = trim((string)($payload["start_date"] ?? ""));
    $endDate = trim((string)($payload["end_date"] ?? ""));

    if ($ay === "" || $semester === "") {
        echo json_encode(["success" => false, "message" => "Missing fields"]);
        exit;
    }

    // Default duration: today -> +1 month (server time). Client may override.
    $start = date("Y-m-d");
    $end = date("Y-m-d", strtotime("+1 month"));

    if ($startDate !== "" && $endDate !== "") {
        $startTs = strtotime($startDate);
        $endTs = strtotime($endDate);
        if ($startTs === false || $endTs === false) {
            echo json_encode(["success" => false, "message" => "Invalid dates"]);
            exit;
        }
        $start = date("Y-m-d", $startTs);
        $end = date("Y-m-d", $endTs);
    }

    if (strtotime($start) === false || strtotime($end) === false || strtotime($start) >= strtotime($end)) {
        echo json_encode(["success" => false, "message" => "End date must be after start date"]);
        exit;
    }
    if ($start < todayDate()) {
        echo json_encode(["success" => false, "message" => "Start date cannot be in the past"]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO evaluation_periods (ay, semester, start_date, end_date, is_active) VALUES (?, ?, ?, ?, 0)");
    $stmt->bind_param("ssss", $ay, $semester, $start, $end);
    $ok = $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();

    echo json_encode(["success" => $ok, "id" => $newId]);
    exit;
}

if ($action === "update") {
    $id = intval($payload["id"] ?? 0);
    $ay = trim((string)($payload["ay"] ?? ""));
    $semester = trim((string)($payload["semester"] ?? ""));
    $startDate = trim((string)($payload["start_date"] ?? ""));
    $endDate = trim((string)($payload["end_date"] ?? ""));

    if ($id <= 0 || $ay === "" || $semester === "" || $startDate === "" || $endDate === "") {
        echo json_encode(["success" => false, "message" => "Missing fields"]);
        exit;
    }

    $startTs = strtotime($startDate);
    $endTs = strtotime($endDate);
    if ($startTs === false || $endTs === false) {
        echo json_encode(["success" => false, "message" => "Invalid dates"]);
        exit;
    }
    $start = date("Y-m-d", $startTs);
    $end = date("Y-m-d", $endTs);
    if (strtotime($start) >= strtotime($end)) {
        echo json_encode(["success" => false, "message" => "End date must be after start date"]);
        exit;
    }
    if ($start < todayDate()) {
        echo json_encode(["success" => false, "message" => "Start date cannot be in the past"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE evaluation_periods SET ay = ?, semester = ?, start_date = ?, end_date = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $ay, $semester, $start, $end, $id);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(["success" => $ok]);
    exit;
}

if ($action === "delete") {
    $id = intval($payload["id"] ?? 0);
    if ($id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid id"]);
        exit;
    }

    $settingsRes = $conn->query("SELECT active_period_id FROM evaluation_settings WHERE id = 1 LIMIT 1");
    $settings = $settingsRes ? $settingsRes->fetch_assoc() : null;
    $activePeriodId = $settings ? intval($settings["active_period_id"] ?? 0) : 0;

    $stmt = $conn->prepare("DELETE FROM evaluation_periods WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok && $activePeriodId === $id) {
        $conn->query("UPDATE evaluation_settings SET evaluation_open = 0, active_period_id = NULL WHERE id = 1");
    }

    echo json_encode(["success" => $ok]);
    exit;
}

if ($action === "set_active") {
    $id = intval($payload["id"] ?? 0);
    if ($id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid id"]);
        exit;
    }

    $today = todayDate();
    $periodStmt = $conn->prepare("SELECT start_date, end_date FROM evaluation_periods WHERE id = ? LIMIT 1");
    $periodStmt->bind_param("i", $id);
    $periodStmt->execute();
    $period = $periodStmt->get_result()->fetch_assoc();
    $periodStmt->close();

    if (!$period) {
        echo json_encode(["success" => false, "message" => "Period not found"]);
        exit;
    }
    if ($period["start_date"] > $today) {
        echo json_encode(["success" => false, "message" => "Period has not started yet"]);
        exit;
    }
    if ($period["end_date"] < $today) {
        echo json_encode(["success" => false, "message" => "Period has already ended"]);
        exit;
    }

    $conn->begin_transaction();
    try {
        $conn->query("UPDATE evaluation_periods SET is_active = 0");
        $stmt = $conn->prepare("UPDATE evaluation_periods SET is_active = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // When active period set: open evaluation.
        $stmt2 = $conn->prepare("UPDATE evaluation_settings SET evaluation_open = 1, active_period_id = ? WHERE id = 1");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $stmt2->close();

        $conn->commit();
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    exit;
}

if ($action === "deactivate") {
    // Turn off active period and close evaluation
    $conn->begin_transaction();
    try {
        $conn->query("UPDATE evaluation_periods SET is_active = 0");
        $conn->query("UPDATE evaluation_settings SET evaluation_open = 0, active_period_id = NULL WHERE id = 1");
        $conn->commit();
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    exit;
}

if ($action === "set_open") {
    $open = intval($payload["open"] ?? 0) === 1 ? 1 : 0;

    // Only allow open if there is an active period.
    if ($open === 1) {
        $res = $conn->query("
            SELECT es.active_period_id, ep.start_date, ep.end_date
            FROM evaluation_settings es
            LEFT JOIN evaluation_periods ep ON ep.id = es.active_period_id
            WHERE es.id = 1
            LIMIT 1
        ");
        $row = $res ? $res->fetch_assoc() : null;
        $activePeriodId = $row ? intval($row["active_period_id"] ?? 0) : 0;
        if ($activePeriodId <= 0) {
            echo json_encode(["success" => false, "message" => "No active period"]);
            exit;
        }
        $today = todayDate();
        if (($row["start_date"] ?? "") > $today) {
            echo json_encode(["success" => false, "message" => "Period has not started yet"]);
            exit;
        }
        if (($row["end_date"] ?? "") < $today) {
            echo json_encode(["success" => false, "message" => "Period has already ended"]);
            exit;
        }
    }

    $stmt = $conn->prepare("UPDATE evaluation_settings SET evaluation_open = ? WHERE id = 1");
    $stmt->bind_param("i", $open);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(["success" => $ok]);
    exit;
}

echo json_encode(["success" => false, "message" => "Unknown action"]);
?>

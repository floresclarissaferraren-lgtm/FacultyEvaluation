<?php
session_start();
header("Content-Type: application/json");
include "connect.php";

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
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_active_period (active_period_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Ensure singleton row exists.
    $conn->query("INSERT IGNORE INTO evaluation_settings (id, evaluation_open, active_period_id) VALUES (1, 0, NULL)");
}

function requireAdmin(): void {
    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
        exit;
    }
}

ensurePeriodTables($conn);

$action = $_GET["action"] ?? "";

// Public-ish read used by other pages
if ($action === "status") {
    $settingsRes = $conn->query("SELECT evaluation_open, active_period_id FROM evaluation_settings WHERE id = 1 LIMIT 1");
    $settings = $settingsRes ? $settingsRes->fetch_assoc() : null;
    $evaluationOpen = $settings ? intval($settings["evaluation_open"]) === 1 : false;
    $activePeriodId = $settings ? intval($settings["active_period_id"] ?? 0) : 0;

    $activeName = null;
    if ($activePeriodId > 0) {
        $stmt = $conn->prepare("SELECT ay, semester FROM evaluation_periods WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $activePeriodId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $sem = (string)($row["semester"] ?? "");
            $prefix = $sem;
            if (stripos($sem, "1st") !== false) $prefix = "1st";
            else if (stripos($sem, "2nd") !== false) $prefix = "2nd";
            $activeName = trim($prefix . " " . (string)($row["ay"] ?? ""));
        }
    }

    echo json_encode([
        "success" => true,
        "evaluation_open" => $evaluationOpen,
        "active_period_id" => $activePeriodId > 0 ? $activePeriodId : null,
        "active_period_name" => $activeName
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
        $res = $conn->query("SELECT active_period_id FROM evaluation_settings WHERE id = 1 LIMIT 1");
        $row = $res ? $res->fetch_assoc() : null;
        $activePeriodId = $row ? intval($row["active_period_id"] ?? 0) : 0;
        if ($activePeriodId <= 0) {
            echo json_encode(["success" => false, "message" => "No active period"]);
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

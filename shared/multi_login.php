<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/rate_limit.php";
requireMethod("POST");
$username = trim($_POST["username"] ?? "");
$password = trim($_POST["password"] ?? "");
enforceRateLimit("login_ip", 300, 900);
enforceRateLimit(
    "login_account_" . hash("sha256", strtolower($username)),
    10,
    900,
);
header("Content-Type: application/json");
include __DIR__ . "/connect.php";

if (empty($username) || empty($password)) {
    echo json_encode([
        "success" => false,
        "message" => "Please fill all fields.",
    ]);
    exit();
}

// Student login by student number
if (str_starts_with(strtoupper($username), "GC-")) {
    $stmt = $conn->prepare(
        "SELECT id, password, status FROM add_students WHERE student_number = ?",
    );
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if ($row["password"] === null) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid username or password.",
            ]);
            $stmt->close();
            $conn->close();
            exit();
        }

        if (password_verify($password, $row["password"])) {
            if (strtolower(trim((string) ($row["status"] ?? "active"))) === "archived") {
                echo json_encode([
                    "success" => false,
                    "message" => "This student account is archived and cannot log in. Contact an administrator to restore it.",
                ]);
                $stmt->close();
                $conn->close();
                exit();
            }

            $log = $conn->prepare(
                "INSERT INTO student_login (username, password) VALUES (?, ?) ON DUPLICATE KEY UPDATE password = VALUES(password)",
            );
            $log->bind_param("ss", $username, $row["password"]);
            $log->execute();
            $log->close();

            rotateSessionSecurityState();
            $_SESSION["role"] = "student";
            $_SESSION["id"] = $row["id"];
            $_SESSION["username"] = $username;
            $_SESSION["account_status"] = $row["status"] ?? "active";
            echo json_encode([
                "success" => true,
                "role" => "student",
                "inactive" =>
                    strtolower((string) ($row["status"] ?? "active")) !==
                    "active",
                "message" =>
                    strtolower((string) ($row["status"] ?? "active")) !==
                    "active"
                        ? "Your account has been set to inactive by an admin. You cannot evaluate until your account is active again."
                        : "",
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Invalid username or password.",
            ]);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Invalid username or password.",
        ]);
    }
    $stmt->close();
    $conn->close();
    exit();
}

// Faculty login

$stmt = $conn->prepare("
    SELECT
        af.id AS profile_id,
        COALESCE(fl.faculty_id, af.id) AS faculty_id,
        COALESCE(fl.faculty_username, af.faculty_id) AS faculty_username,
        COALESCE(fl.faculty_password, af.password) AS faculty_password,
        (fl.faculty_id IS NULL) AS login_missing,
        COALESCE(af.status, 'active') AS status
    FROM add_faculties af
    LEFT JOIN faculty_login fl ON fl.faculty_id = af.id
    WHERE af.faculty_id = ?
");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user["faculty_password"])) {
        if (strtolower(trim((string) ($user["status"] ?? "active"))) === "archived") {
            echo json_encode([
                "success" => false,
                "message" => "This faculty account is archived and cannot log in. Contact an administrator to restore it.",
            ]);
            $stmt->close();
            $conn->close();
            exit();
        }

        if ((int) $user["login_missing"] === 1) {
            $repairStmt = $conn->prepare(
                "INSERT INTO faculty_login (faculty_id, faculty_username, faculty_password) VALUES (?, ?, ?)",
            );
            if ($repairStmt) {
                $repairStmt->bind_param(
                    "iss",
                    $user["profile_id"],
                    $user["faculty_username"],
                    $user["faculty_password"],
                );
                $repairStmt->execute();
                $repairStmt->close();
            }
        }
        rotateSessionSecurityState();
        $_SESSION["role"] = "faculty";
        $_SESSION["id"] = $user["faculty_id"];
        $_SESSION["username"] = $user["faculty_username"];
        $_SESSION["account_status"] = $user["status"] ?? "active";
        echo json_encode([
            "success" => true,
            "role" => "faculty",
            "inactive" =>
                strtolower((string) ($user["status"] ?? "active")) !== "active",
            "message" =>
                strtolower((string) ($user["status"] ?? "active")) !== "active"
                    ? "Your account has been set to inactive by an admin. You cannot generate or view result until your account is active again."
                    : "",
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Invalid username or password.",
        ]);
    }
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

// Admin login fallback
$stmt = $conn->prepare(
    "SELECT admin_id, admin_username, admin_password FROM admin_login WHERE admin_username = ?",
);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user["admin_password"])) {
        rotateSessionSecurityState();
        $_SESSION["role"] = "admin";
        $_SESSION["id"] = $user["admin_id"];
        $_SESSION["username"] = $user["admin_username"];
        echo json_encode(["success" => true, "role" => "admin"]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Invalid username or password.",
        ]);
    }
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

echo json_encode([
    "success" => false,
    "message" => "Invalid username or password.",
]);
$conn->close();
?>

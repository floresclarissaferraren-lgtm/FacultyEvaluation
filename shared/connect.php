<?php
require_once __DIR__ . "/session_config.php";

$host = getenv("FACULTY_DB_HOST");
$user = getenv("FACULTY_DB_USER");
$pass = getenv("FACULTY_DB_PASSWORD");
$db = getenv("FACULTY_DB_NAME");

$hasDatabaseConfig =
    ($host !== false && $host !== "") ||
    ($user !== false && $user !== "") ||
    ($pass !== false && $pass !== "") ||
    ($db !== false && $db !== "");

if (!$hasDatabaseConfig) {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "faculty_evaluation_db";
}

if (
    $host === false ||
    $host === "" ||
    $user === false ||
    $user === "" ||
    ($hasDatabaseConfig && strtolower($user) === "root") ||
    $pass === false ||
    ($hasDatabaseConfig && $pass === "") ||
    $db === false ||
    $db === ""
) {
    error_log(
        "Faculty Evaluation database configuration is incomplete or uses a disallowed database account.",
    );
    http_response_code(500);
    exit(
        "Database configuration is incomplete. Configure all FACULTY_DB_* values or remove them to use local XAMPP defaults."
    );
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $exception) {
    error_log(
        "Faculty Evaluation database connection failed: " .
            $exception->getMessage(),
    );
    http_response_code(500);
    exit("Database connection unavailable.");
}

<?php

function enforceRateLimit(string $bucket, int $limit, int $windowSeconds): void
{
    $clientIp = (string) ($_SERVER["REMOTE_ADDR"] ?? "");
    if ($clientIp === "" || $limit < 1 || $windowSeconds < 1) {
        error_log(
            "Rate limit could not be applied because request metadata or limits are invalid.",
        );
        http_response_code(503);
        exit("Security control unavailable.");
    }

    $directory =
        rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) .
        DIRECTORY_SEPARATOR .
        "faculty_evaluation_rate_limits_" .
        substr(hash("sha256", dirname(__DIR__)), 0, 16);
    if (
        !is_dir($directory) &&
        !mkdir($directory, 0700, true) &&
        !is_dir($directory)
    ) {
        error_log(
            "Unable to create the faculty evaluation rate-limit storage directory.",
        );
        http_response_code(503);
        exit("Security control unavailable.");
    }

    $file = $directory . DIRECTORY_SEPARATOR . "attempts.json";
    $handle = @fopen($file, "c+");
    if ($handle === false || !flock($handle, LOCK_EX)) {
        if (is_resource($handle)) {
            fclose($handle);
        }
        error_log("Unable to lock faculty evaluation rate-limit storage.");
        http_response_code(503);
        exit("Security control unavailable.");
    }

    $now = time();
    $key = hash("sha256", $bucket . "\0" . $clientIp);
    rewind($handle);
    $contents = stream_get_contents($handle);
    $state = $contents === "" ? [] : json_decode($contents, true);
    if (!is_array($state)) {
        flock($handle, LOCK_UN);
        fclose($handle);
        error_log(
            "Faculty evaluation rate-limit storage contains invalid data.",
        );
        http_response_code(503);
        exit("Security control unavailable.");
    }

    foreach ($state as $storedKey => $entry) {
        if (
            !is_array($entry) ||
            (int) ($entry["started_at"] ?? 0) + (int) ($entry["window"] ?? 0) <=
                $now
        ) {
            unset($state[$storedKey]);
        }
    }

    $entry = $state[$key] ?? [
        "started_at" => $now,
        "window" => $windowSeconds,
        "count" => 0,
    ];
    if ($entry["started_at"] + $entry["window"] <= $now) {
        $entry = [
            "started_at" => $now,
            "window" => $windowSeconds,
            "count" => 0,
        ];
    }

    if ($entry["count"] >= $limit) {
        $retryAfter = max(1, $entry["started_at"] + $entry["window"] - $now);
        $accountType = "";

        if (!empty($_POST["account_type"])) {
            $accountType = strtolower(trim((string) $_POST["account_type"]));
        } else {
            $body = @file_get_contents("php://input");
            if (is_string($body) && $body !== "") {
                $decoded = json_decode($body, true);
                if (is_array($decoded)) {
                    $accountType = strtolower(
                        trim((string) ($decoded["account_type"] ?? "")),
                    );
                } else {
                    parse_str($body, $parsedBody);
                    $accountType = strtolower(
                        trim((string) ($parsedBody["account_type"] ?? "")),
                    );
                }
            }
        }

        $retryMessage =
            $accountType === "admin"
                ? "This email address is not registered as the administrator's email. Please enter the registered admin Gmail address."
                : "Too many attempts. Please wait before trying again.";

        flock($handle, LOCK_UN);
        fclose($handle);
        header("Retry-After: " . $retryAfter);
        http_response_code(429);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "success" => false,
            "message" => $retryMessage,
        ]);
        exit();
    }

    $entry["count"]++;
    $state[$key] = $entry;
    rewind($handle);
    if (
        !ftruncate($handle, 0) ||
        fwrite($handle, json_encode($state)) === false ||
        !fflush($handle)
    ) {
        flock($handle, LOCK_UN);
        fclose($handle);
        error_log("Unable to persist faculty evaluation rate-limit state.");
        http_response_code(503);
        exit("Security control unavailable.");
    }

    flock($handle, LOCK_UN);
    fclose($handle);
}

<?php
/**
 * notify_evaluation_open.php
 *
 * Sends email notifications to students when evaluation opens.
 */

require_once __DIR__ . "/mailer.php";

function sendEvaluationOpenBatch(
    array $recipients,
    string $subject,
    string $body,
): array {
    $sent = 0;
    $failed = 0;
    $firstError = "";

    foreach (array_chunk($recipients, 50) as $batch) {
        $batchSize = count($batch);
        if (
            sendEmail(
                MAIL_USER,
                MAIL_NAME,
                $subject,
                $body,
                null,
                null,
                [],
                true,
                $batch,
            )
        ) {
            $sent += $batchSize;
        } else {
            $failed += $batchSize;
            if ($firstError === "") {
                $firstError = getLastEmailError();
            }
            error_log(
                "Evaluation opening email batch failed for {$batchSize} recipients: " .
                    getLastEmailError(),
            );
        }
    }

    return [
        "sent" => $sent,
        "failed" => $failed,
        "error" => $firstError,
    ];
}

function sendEvaluationOpenNotifications(
    mysqli $conn,
    string $ay,
    string $semester,
): array {
    $protocol =
        !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off"
            ? "https"
            : "http";
    $host = $_SERVER["HTTP_HOST"] ?? "localhost";
    $baseUrl = $protocol . "://" . $host . "/FacultyEvaluation";
    $subject = "Faculty Evaluation Period Opened - A.Y. $ay, $semester";
    $studentContent =
        "
        <p style='margin: 0 0 16px 0;'>The faculty evaluation period for <strong>Academic Year " .
        htmlspecialchars($ay, ENT_QUOTES, "UTF-8") .
        ", " .
        htmlspecialchars($semester, ENT_QUOTES, "UTF-8") .
        "</strong> has officially opened.</p>
        <p style='margin: 0 0 16px 0;'>Please log in to your student account at your earliest convenience to evaluate your instructors. Your feedback is highly appreciated and helps us maintain and improve our educational standards.</p>
    ";
    $facultyContent =
        "
        <p style='margin: 0 0 16px 0;'>The faculty evaluation period for <strong>Academic Year " .
        htmlspecialchars($ay, ENT_QUOTES, "UTF-8") .
        ", " .
        htmlspecialchars($semester, ENT_QUOTES, "UTF-8") .
        "</strong> is now open.</p>
        <p style='margin: 0 0 16px 0;'>Students may submit evaluations during the scheduled period. Please log in to your faculty account to access the evaluation system.</p>
    ";
    $cta = [
        "label" => "Access Evaluation System",
        "url" => $baseUrl . "/EvalMain.php",
    ];

    // Send in batches to reduce SMTP round trips without exposing recipients.
    $studentRes = $conn->query("
        SELECT DISTINCT email
        FROM add_students
        WHERE LOWER(TRIM(status)) = 'active'
          AND email IS NOT NULL
          AND email != ''
    ");
    $facultyRes = $conn->query("
        SELECT DISTINCT email
        FROM add_faculties
        WHERE LOWER(TRIM(COALESCE(status, 'active'))) = 'active'
          AND email IS NOT NULL
          AND email != ''
    ");

    $studentBody = getEmailHTML(
        "Faculty Evaluation Period Opened",
        "Dear Student,",
        $studentContent,
        null,
        $cta,
    );
    $facultyBody = getEmailHTML(
        "Faculty Evaluation Period Opened",
        "Dear Faculty Member,",
        $facultyContent,
        null,
        $cta,
    );
    $studentResult = ["sent" => 0, "failed" => 0, "error" => ""];
    $facultyResult = ["sent" => 0, "failed" => 0, "error" => ""];

    if (!$studentRes) {
        $studentResult["failed"] = 1;
        $studentResult["error"] = "Unable to load active student email addresses.";
        error_log("Evaluation opening notices could not load active students.");
    } else {
        $studentRecipients = [];
        while ($row = $studentRes->fetch_assoc()) {
            $email = trim((string) ($row["email"] ?? ""));
            if ($email !== "") {
                $studentRecipients[] = $email;
            }
        }
        $studentResult = sendEvaluationOpenBatch(
            $studentRecipients,
            $subject,
            $studentBody,
        );
    }

    if (!$facultyRes) {
        $facultyResult["failed"] = 1;
        $facultyResult["error"] = "Unable to load active faculty email addresses.";
        error_log("Evaluation opening notices could not load active faculty.");
    } else {
        $facultyRecipients = [];
        while ($row = $facultyRes->fetch_assoc()) {
            $email = trim((string) ($row["email"] ?? ""));
            if ($email !== "") {
                $facultyRecipients[] = $email;
            }
        }
        $facultyResult = sendEvaluationOpenBatch(
            $facultyRecipients,
            $subject,
            $facultyBody,
        );
    }

    $errors = array_values(
        array_filter([$studentResult["error"], $facultyResult["error"]]),
    );

    return [
        "sent" => $studentResult["sent"] + $facultyResult["sent"],
        "failed" => $studentResult["failed"] + $facultyResult["failed"],
        "students_sent" => $studentResult["sent"],
        "students_failed" => $studentResult["failed"],
        "faculty_sent" => $facultyResult["sent"],
        "faculty_failed" => $facultyResult["failed"],
        "errors" => $errors,
    ];
}
?>

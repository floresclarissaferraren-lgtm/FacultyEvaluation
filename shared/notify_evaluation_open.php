<?php
/**
 * notify_evaluation_open.php
 *
 * Sends email notifications to students when evaluation opens.
 */

require_once __DIR__ . "/mailer.php";

function sendEvaluationOpenNotifications(
    mysqli $conn,
    string $ay,
    string $semester,
): array {
    // Select all active students with a valid email
    $res = $conn->query("
        SELECT email, firstname, lastname 
        FROM add_students 
        WHERE LOWER(status) = 'active' 
          AND email IS NOT NULL 
          AND email != ''
    ");

    $sent = 0;
    $failed = 0;
    $firstError = "";

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $email = trim($row["email"]);
            $name = trim($row["firstname"] . " " . $row["lastname"]);

            $subject = "Faculty Evaluation Period Opened - A.Y. $ay, $semester";
            $protocol =
                !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off"
                    ? "https"
                    : "http";
            $host = $_SERVER["HTTP_HOST"] ?? "localhost";
            $baseUrl = $protocol . "://" . $host . "/FacultyEvaluation";

            $title = "Faculty Evaluation Period Opened";
            $greeting =
                "Dear " . htmlspecialchars($name, ENT_QUOTES, "UTF-8") . ",";
            $content_html =
                "
                <p style='margin: 0 0 16px 0;'>The faculty evaluation period for <strong>Academic Year " .
                htmlspecialchars($ay, ENT_QUOTES, "UTF-8") .
                ", " .
                htmlspecialchars($semester, ENT_QUOTES, "UTF-8") .
                "</strong> has officially opened.</p>
                <p style='margin: 0 0 16px 0;'>Please log in to your student account at your earliest convenience to evaluate your instructors. Your feedback is highly appreciated and helps us maintain and improve our educational standards.</p>
            ";

            $cta = [
                "label" => "Access Evaluation System",
                "url" => $baseUrl . "/EvalMain.php",
            ];

            $body = getEmailHTML($title, $greeting, $content_html, null, $cta);

            // Use the same Gmail SMTP connection mode as the working close
            // report sender so opening notices follow the proven mail path.
            if (sendEmail($email, $name, $subject, $body, null, null, [], true)) {
                $sent++;
            } else {
                $failed++;
                if ($firstError === "") {
                    $firstError = getLastEmailError();
                }
                error_log(
                    "Evaluation opening email failed for {$email}: " .
                        getLastEmailError(),
                );
            }
        }
    }

    return [
        "sent" => $sent,
        "failed" => $failed,
        "errors" => $firstError === "" ? [] : [$firstError],
    ];
}
?>

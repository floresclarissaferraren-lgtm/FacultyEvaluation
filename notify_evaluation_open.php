<?php
/**
 * notify_evaluation_open.php
 * Called internally after a period is opened.
 * Sends a "Evaluation Period Now Open" email to every active student.
 *
 * Expects to be included (not called directly) AFTER connect.php & mailer.php are loaded,
 * OR it can be called as a standalone POST endpoint from JS.
 *
 * POST body (JSON): { "ay": "2026-2027", "semester": "1st Semester" }
 * Returns JSON: { "success": true, "sent": N, "failed": N, "errors": [...] }
 */

// Allow standalone AJAX call
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    include_once 'session_config.php';
    session_start();
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    include 'connect.php';
    require_once 'mailer.php';

    $payload = json_decode(file_get_contents('php://input'), true);
    $ay       = trim((string)($payload['ay']       ?? ''));
    $semester = trim((string)($payload['semester'] ?? ''));

    $result = sendEvaluationOpenNotifications($conn, $ay, $semester);
    echo json_encode($result);
    exit;
}

/**
 * Send evaluation-open emails to all active students.
 *
 * @param mysqli $conn
 * @param string $ay       e.g. "2026-2027"
 * @param string $semester e.g. "1st Semester"
 * @return array { success, sent, failed, errors }
 */
function sendEvaluationOpenNotifications(mysqli $conn, string $ay, string $semester): array
{
    // Fetch all active students with an email
    $res = $conn->query("
        SELECT firstname, lastname, email
        FROM add_students
        WHERE status != 'archived'
          AND email IS NOT NULL
          AND email != ''
        ORDER BY lastname, firstname
    ");

    if (!$res) {
        return ['success' => false, 'sent' => 0, 'failed' => 0,
                'errors' => ['DB query failed: ' . $conn->error]];
    }

    $students = $res->fetch_all(MYSQLI_ASSOC);

    if (empty($students)) {
        return ['success' => true, 'sent' => 0, 'failed' => 0, 'errors' => []];
    }

    // Build display strings
    $ayDisplay       = $ay       !== '' ? $ay       : 'N/A';
    $semesterDisplay = $semester !== '' ? $semester : 'N/A';

    $subject = 'Faculty Evaluation Period Now Open';

    $sent   = 0;
    $failed = 0;
    $errors = [];

    foreach ($students as $student) {
        $firstName = htmlspecialchars($student['firstname'] ?? '', ENT_QUOTES, 'UTF-8');
        $fullName  = trim(($student['firstname'] ?? '') . ' ' . ($student['lastname'] ?? ''));
        $email     = $student['email'];

        $body = buildEvaluationOpenEmail($firstName, $ayDisplay, $semesterDisplay);

        $ok = sendEmail($email, $fullName, $subject, $body);
        if ($ok) {
            $sent++;
        } else {
            $failed++;
            $errors[] = "Failed to send to {$email}: " . getLastEmailError();
        }
    }

    return [
        'success' => true,
        'sent'    => $sent,
        'failed'  => $failed,
        'errors'  => $errors,
    ];
}

/**
 * Build the HTML email body.
 */
function buildEvaluationOpenEmail(string $firstName, string $ay, string $semester): string
{
    return '
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 16px;">
    <tr>
      <td align="center">

        <!-- Card -->
        <table width="100%" cellpadding="0" cellspacing="0"
               style="max-width:560px;background:#ffffff;border-radius:12px;
                      box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;">

          <!-- Header banner -->
          <tr>
            <td style="background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);
                       padding:32px 36px;text-align:center;">
              <p style="margin:0 0 6px;color:#bfdbfe;font-size:13px;
                        letter-spacing:.08em;text-transform:uppercase;">
                Faculty Evaluation System
              </p>
              <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;
                         letter-spacing:-.02em;line-height:1.3;">
                Evaluation Period&nbsp;Now Open
              </h1>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:32px 36px;">

              <p style="margin:0 0 16px;font-size:15px;color:#1e293b;line-height:1.6;">
                Good day, <strong>' . $firstName . '</strong>.
              </p>

              <p style="margin:0 0 24px;font-size:15px;color:#334155;line-height:1.7;">
                The faculty evaluation period is now officially open.
                You may proceed to evaluate your instructors for the current academic term.
              </p>

              <!-- Info box -->
              <table width="100%" cellpadding="0" cellspacing="0"
                     style="background:#eff6ff;border:1px solid #bfdbfe;
                            border-radius:8px;margin-bottom:28px;">
                <tr>
                  <td style="padding:20px 24px;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="font-size:13px;color:#64748b;
                                   font-weight:600;text-transform:uppercase;
                                   letter-spacing:.06em;padding-bottom:12px;"
                            colspan="2">
                          Evaluation Details
                        </td>
                      </tr>
                      <tr>
                        <td style="font-size:14px;color:#475569;
                                   font-weight:500;padding:4px 0;width:140px;">
                          Academic Year
                        </td>
                        <td style="font-size:14px;color:#1e3a8a;
                                   font-weight:700;padding:4px 0;">
                          ' . htmlspecialchars($ay, ENT_QUOTES, 'UTF-8') . '
                        </td>
                      </tr>
                      <tr>
                        <td style="font-size:14px;color:#475569;
                                   font-weight:500;padding:4px 0;">
                          Semester
                        </td>
                        <td style="font-size:14px;color:#1e3a8a;
                                   font-weight:700;padding:4px 0;">
                          ' . htmlspecialchars($semester, ENT_QUOTES, 'UTF-8') . '
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 8px;font-size:13px;color:#94a3b8;line-height:1.6;">
                Please complete your evaluation before the period closes. Your feedback
                helps improve the quality of instruction in your institution.
              </p>

            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f8fafc;border-top:1px solid #e2e8f0;
                       padding:20px 36px;text-align:center;">
              <p style="margin:0;font-size:12px;color:#94a3b8;">
                This is an automated message from the Faculty Evaluation System.
                Please do not reply to this email.
              </p>
            </td>
          </tr>

        </table>
        <!-- /Card -->

      </td>
    </tr>
  </table>

</body>
</html>';
}
?>

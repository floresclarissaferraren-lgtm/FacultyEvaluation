<?php
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';
require __DIR__ . '/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* =========================
   CONFIG
========================= */
define("MAIL_USER", "floresclarissaferraren@gmail.com");
define("MAIL_PASS", "nicj elgi ruam ozca");
define("MAIL_NAME", "Faculty Evaluation System");

$lastEmailError = '';

function getLastEmailError()
{
    global $lastEmailError;
    return $lastEmailError;
}

/* =========================
   SEND EMAIL FUNCTION
========================= */
function sendEmail($toEmail, $toName, $subject, $body)
{
    global $lastEmailError;
    $lastEmailError = '';

    if (!extension_loaded('openssl')) {
        $lastEmailError = 'OpenSSL extension is disabled. Enable OpenSSL in PHP/XAMPP before sending Gmail SMTP email.';
        error_log("MAIL ERROR: " . $lastEmailError);
        return false;
    }

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = MAIL_USER;
        $mail->Password = preg_replace('/\s+/', '', MAIL_PASS);

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->SMTPAutoTLS = true;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->setFrom(MAIL_USER, MAIL_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body)));

        if (!$mail->send()) {
            $lastEmailError = $mail->ErrorInfo ?: 'Unknown mail error.';
            error_log("MAIL ERROR: " . $lastEmailError);
            return false;
        }

        return true;

    } catch (Exception $e) {
        $lastEmailError = $mail->ErrorInfo ?: $e->getMessage();
        error_log("MAIL ERROR: " . $lastEmailError);
        return false;
    }
}
?>

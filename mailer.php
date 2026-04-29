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

/* =========================
   SEND EMAIL FUNCTION
========================= */
function sendEmail($toEmail, $toName, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = MAIL_USER;
        $mail->Password = MAIL_PASS;

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
        $mail->Subject = $subject;
        $mail->Body = $body;

        return $mail->send();

    } catch (Exception $e) {
        error_log("MAIL ERROR: " . $mail->ErrorInfo);
        return false;
    }
}
?>
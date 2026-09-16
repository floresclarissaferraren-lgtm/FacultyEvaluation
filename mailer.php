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

/**
 * Generates a visually stunning, responsive, and Gmail-compatible HTML email template.
 *
 * @param string $title Header/Subject title in the email card.
 * @param string $greeting Greeting to the user (e.g. "Hello Professor John Doe").
 * @param string $content_html The body paragraphs, instructions or message.
 * @param array|null $highlight_box Optional array with keys: 'label', 'value', 'subtext'.
 * @param array|null $cta Optional array with keys: 'label', 'url'.
 * @return string HTML email body content.
 */
function getEmailHTML($title, $greeting, $content_html, $highlight_box = null, $cta = null)
{
    $currentYear = date('Y');
    
    // Highlight box section
    $highlightBoxHTML = '';
    if ($highlight_box && is_array($highlight_box)) {
        $label = htmlspecialchars($highlight_box['label'] ?? 'Password');
        $value = htmlspecialchars($highlight_box['value'] ?? '');
        $subtext = htmlspecialchars($highlight_box['subtext'] ?? '');
        
        $subtextHTML = '';
        if ($subtext !== '') {
            $subtextHTML = "
                      <tr>
                        <td style='font-size: 13px; color: #0284c7; font-weight: 500; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>
                          {$subtext}
                        </td>
                      </tr>";
        }
        
        $highlightBoxHTML = "
                <tr>
                  <td style='padding-bottom: 28px;'>
                    <table border='0' cellpadding='0' cellspacing='0' width='100%' style='background-color: #f0f9ff; border: 1px dashed #0284c7; border-radius: 12px; padding: 24px; text-align: center;'>
                      <tr>
                        <td style='font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #0369a1; padding-bottom: 8px; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>
                          {$label}
                        </td>
                      </tr>
                      <tr>
                        <td class='code-display' style='font-family: Consolas, \"Courier New\", Courier, monospace; font-size: 32px; font-weight: 800; color: #0369a1; letter-spacing: 2px; padding-bottom: 6px;'>
                          {$value}
                        </td>
                      </tr>
                      {$subtextHTML}
                    </table>
                  </td>
                </tr>";
    }

    // Call to action button section
    $ctaHTML = '';
    if ($cta && is_array($cta)) {
        $ctaLabel = htmlspecialchars($cta['label'] ?? 'Click Here');
        $ctaUrl = htmlspecialchars($cta['url'] ?? '#');
        $ctaHTML = "
                <tr>
                  <td align='center' style='padding-bottom: 28px;'>
                    <table border='0' cellpadding='0' cellspacing='0'>
                      <tr>
                        <td align='center' bgcolor='#2563eb' style='border-radius: 8px;'>
                          <a href='{$ctaUrl}' target='_blank' style='display: inline-block; font-size: 14px; font-weight: 600; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 8px; border: 1px solid #2563eb; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>
                            {$ctaLabel}
                          </a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>";
    }

    // Main HTML template construction
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Faculty Evaluation System</title>
  <style>
    @media only screen and (max-width: 620px) {
      .email-container {
        width: 100% !important;
        padding: 10px !important;
      }
      .email-card {
        border-radius: 12px !important;
        padding: 24px 16px !important;
      }
      .header-title {
        font-size: 20px !important;
      }
      .code-display {
        font-size: 26px !important;
        letter-spacing: 2px !important;
      }
    }
  </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
  <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; padding: 40px 0;">
    <tr>
      <td align="center" valign="top">
        <!-- Container -->
        <table class="email-container" border="0" cellpadding="0" cellspacing="0" width="600" style="margin: 0 auto; width: 600px; max-width: 600px;">
          
          <!-- Branded Header -->
          <tr>
            <td style="padding: 0 0 24px 0; text-align: center;">
              <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                  <td align="center" style="padding-bottom: 8px;">
                    <span style="display: inline-block; background-color: #1e3a8a; color: #ffffff; padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 13px; letter-spacing: 1px; text-transform: uppercase; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">GCST</span>
                  </td>
                </tr>
                <tr>
                  <td align="center">
                    <h1 class="header-title" style="margin: 0; font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; line-height: 1.2; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">Granby Colleges of Science & Technology</h1>
                    <span style="font-size: 11px; color: #64748b; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">Faculty Evaluation System</span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          
          <!-- Main Card -->
          <tr>
            <td class="email-card" style="background-color: #ffffff; padding: 40px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.025); border: 1px solid #e2e8f0;">
              <table border="0" cellpadding="0" cellspacing="0" width="100%">
                
                <!-- Subtitle / Email Subject Header -->
                <tr>
                  <td style="padding-bottom: 24px; border-bottom: 1px solid #f1f5f9;">
                    <span style="display: inline-block; background-color: #eff6ff; color: #2563eb; font-size: 11px; font-weight: 700; padding: 6px 12px; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.5px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">{$title}</span>
                  </td>
                </tr>
                
                <!-- Greeting -->
                <tr>
                  <td style="padding-top: 24px; padding-bottom: 16px;">
                    <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: #1e293b; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">{$greeting}</h2>
                  </td>
                </tr>
                
                <!-- Content / Instructions -->
                <tr>
                  <td style="font-size: 15px; line-height: 1.6; color: #475569; padding-bottom: 24px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                    {$content_html}
                  </td>
                </tr>
                
                <!-- Highlighted Password / Code Box -->
                {$highlightBoxHTML}
                
                <!-- Call To Action Button -->
                {$ctaHTML}
                
                <!-- Extra warning / helper info -->
                <tr>
                  <td style="font-size: 13px; line-height: 1.5; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                    This is an automated message from the Granby Colleges Faculty Evaluation System. Please do not reply directly to this email. If you did not request this, please secure your account.
                  </td>
                </tr>
                
              </table>
            </td>
          </tr>
          
          <!-- Footer -->
          <tr>
            <td style="padding: 24px 0 0 0; text-align: center;">
              <p style="margin: 0 0 8px 0; font-size: 12px; color: #94a3b8; font-weight: 500; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                &copy; {$currentYear} Granby Colleges of Science and Technology. All rights reserved.
              </p>
              <p style="margin: 0; font-size: 11px; color: #cbd5e1; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                GCST Campus, Naic, Cavite, Philippines
              </p>
            </td>
          </tr>
          
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    return $html;
}

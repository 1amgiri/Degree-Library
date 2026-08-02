<?php
// api/mail_helper.php

require_once 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Sends a HTML email using PHPMailer via Gmail SMTP (with native mail() fallback).
 */
function send_html_email($to, $subject, $body_html)
{
    global $conn;

    // Check if emails are enabled
    $emails_enabled = true;
    if (isset($conn)) {
        $table_check = $conn->query("SHOW TABLES LIKE 'site_settings'");
        if ($table_check && $table_check->num_rows > 0) {
            $res = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'emails_enabled'");
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $emails_enabled = ($row['setting_value'] === '1' || $row['setting_value'] === 'true');
            }
        }
    }

    if (!$emails_enabled) {
        $log_entry = "[" . date('Y-m-d H:i:s') . "] Email sending bypassed (disabled in settings) | Recipient: " . $to . " | Subject: " . $subject . "\n";
        @file_put_contents(__DIR__ . '/mail_debug.log', $log_entry, FILE_APPEND);
        return true;
    }

    // Check if the recipient has notifications disabled specifically
    if (isset($conn) && !empty($to)) {
        $sub_check = $conn->prepare("SELECT enabled FROM subscriptions WHERE email = ?");
        if ($sub_check) {
            $sub_check->bind_param("s", $to);
            $sub_check->execute();
            $sub_res = $sub_check->get_result();
            if ($sub_res && $sub_res->num_rows > 0) {
                $sub_row = $sub_res->fetch_assoc();
                if (isset($sub_row['enabled']) && (int)$sub_row['enabled'] === 0) {
                    $log_entry = "[" . date('Y-m-d H:i:s') . "] Email delivery bypassed (recipient disabled specifically) | Recipient: " . $to . " | Subject: " . $subject . "\n";
                    @file_put_contents(__DIR__ . '/mail_debug.log', $log_entry, FILE_APPEND);
                    return true;
                }
            }
            $sub_check->close();
        }
    }

    // Build retro template wrapper around body_html
    $main_content = $body_html;
    $footer_content = "Free Degree Library Team<br>Helping students learn together.";
    
    // Check if there's a custom footer/unsubscribe link in the body
    if (strpos($body_html, 'Manage Preferences or Unsubscribe') !== false) {
        $footer_content = "Free Degree Library Team - Helping students learn together.<br>You are receiving this because you subscribed to updates.<br><a href='https://degreelibrary.gt.tc/' style='color: #7D84E8; text-decoration: underline; font-weight: bold;'>Manage Preferences or Unsubscribe</a>";
        // Strip out any redundant default unsubscribe paragraph
        $main_content = preg_replace('/<br><hr><p style=\'font-size:11px;.*<\/p>/s', '', $main_content);
        $main_content = preg_replace('/<hr><p style="font-size: 11px;.*<\/p>/s', '', $main_content);
        $main_content = preg_replace('/<br><hr><p style="font-size: 11px;.*<\/p>/s', '', $main_content);
    }

    // Wrap in retro template structure
    $wrapped_body = '<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>' . htmlspecialchars($subject) . '</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F3F4F6;">
  <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #F3F4F6; padding: 20px 0;">
    <tr>
      <td align="center">
        <!-- Main Email Container -->
        <table width="600" border="0" cellpadding="0" cellspacing="0" style="width: 600px; background-color: #D8D9DE; border: 3px solid #2D3347; border-collapse: collapse;">
          <!-- Header -->
          <tr>
            <td align="center" style="background-color: #7D84E8; border-bottom: 3px solid #2D3347; padding: 20px;">
              <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="font-family: \'Courier New\', monospace; font-size: 28px; font-weight: bold; color: #111827; line-height: 1.2;">
                    Free Degree Library
                  </td>
                </tr>
                <tr>
                  <td align="center" style="font-family: \'Courier New\', monospace; font-size: 12px; color: #2D3347; padding-top: 4px; font-weight: bold;">
                    Powered by Cirravo Solutions
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          
          <!-- Body -->
          <tr>
            <td style="background-color: #D8D9DE; padding: 30px; font-family: \'Courier New\', monospace; font-size: 15px; color: #2D3347; line-height: 1.7; text-align: left;">
              ' . $main_content . '
            </td>
          </tr>
          
          <!-- Footer -->
          <tr>
            <td align="center" style="background-color: #1F2942; border-top: 3px solid #2D3347; padding: 15px; font-family: \'Courier New\', monospace; font-size: 12px; color: #FFFFFF; line-height: 1.5;">
              ' . $footer_content . '
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';

    $body_html = $wrapped_body;

    // 1. Try to load PHPMailer (supports Composer and manual directory uploads)
    $phpmailer_loaded = false;
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        $phpmailer_loaded = true;
    } elseif (file_exists(__DIR__ . '/phpmailer/PHPMailer.php')) {
        require_once __DIR__ . '/phpmailer/Exception.php';
        require_once __DIR__ . '/phpmailer/PHPMailer.php';
        require_once __DIR__ . '/phpmailer/SMTP.php';
        $phpmailer_loaded = true;
    } elseif (file_exists(__DIR__ . '/PHPMailer/PHPMailer.php')) {
        require_once __DIR__ . '/PHPMailer/Exception.php';
        require_once __DIR__ . '/PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/SMTP.php';
        $phpmailer_loaded = true;
    } elseif (file_exists(__DIR__ . '/phpmailer/src/PHPMailer.php')) {
        require_once __DIR__ . '/phpmailer/src/Exception.php';
        require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/phpmailer/src/SMTP.php';
        $phpmailer_loaded = true;
    } elseif (file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
        require_once __DIR__ . '/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/src/SMTP.php';
        $phpmailer_loaded = true;
    } elseif (file_exists(__DIR__ . '/PHPMailer-FE_v4.11/src/PHPMailer.php')) {
        require_once __DIR__ . '/PHPMailer-FE_v4.11/src/Exception.php';
        require_once __DIR__ . '/PHPMailer-FE_v4.11/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer-FE_v4.11/src/SMTP.php';
        $phpmailer_loaded = true;
    }

    if ($phpmailer_loaded) {
        $mail = new PHPMailer(true);
        try {
            if (file_exists(__DIR__ . '/secrets.php')) {
                require_once __DIR__ . '/secrets.php';
            } elseif (file_exists(__DIR__ . '/secrets.example.php')) {
                require_once __DIR__ . '/secrets.example.php';
            }
            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->Host = getenv('SMTP_HOST') ?: (defined('CFG_SMTP_HOST') ? CFG_SMTP_HOST : 'smtp.gmail.com');
            $mail->SMTPAuth = true;
            $mail->Username = getenv('SMTP_USER') ?: (defined('CFG_SMTP_USER') ? CFG_SMTP_USER : 'your-email@gmail.com');
            $mail->Password = getenv('SMTP_PASS') ?: (defined('CFG_SMTP_PASS') ? CFG_SMTP_PASS : 'your-gmail-app-password');

            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Sender & Receiver Settings
            $mail->setFrom('cirravosolutions@gmail.com', 'Free Degree Library Alerts');
            $mail->addAddress($to);
            $mail->addReplyTo('cirravosolutions@gmail.com', 'Free Degree Library');

            // Content Format
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body_html;
            $mail->AltBody = strip_tags($body_html);

            // SSL bypass options (helps with shared/local server compatibility)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail->send();
            return true;
        } catch (Exception $e) {
            $log_entry = "[" . date('Y-m-d H:i:s') . "] PHPMailer SMTP Dispatch Failed: " . $mail->ErrorInfo . " | Recipient: " . $to . "\n";
            @file_put_contents(__DIR__ . '/mail_debug.log', $log_entry, FILE_APPEND);
        }
    }

    // 2. Fallback: Native PHP mail() function configured to send from cirravosolutions@gmail.com
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Free Degree Library <cirravosolutions@gmail.com>\r\n";
    $headers .= "Reply-To: cirravosolutions@gmail.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    return @mail($to, $subject, $body_html, $headers);
}

/**
 * Fetches all subscribers matching the specified notification type and dispatches HTML notification emails.
 */
function trigger_group_notifications($subject, $body_html, $preference_column)
{
    global $conn;

    $allowed_columns = ['notify_announcements', 'notify_community', 'notify_materials'];
    if (!in_array($preference_column, $allowed_columns)) {
        return false;
    }

    $query = "SELECT email FROM subscriptions WHERE $preference_column = 1 AND enabled = 1";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $email = $row['email'];
            $unsubscribe_url = "https://degreelibrary.gt.tc/";
            $footer = "<br><hr><p style='font-size:11px;color:#888;text-align:center;'>You are receiving this because you subscribed to updates on Free Degree Library.<br><a href='{$unsubscribe_url}'>Manage Preferences or Unsubscribe</a></p>";
            $full_body = $body_html . $footer;
            send_html_email($email, $subject, $full_body);
        }
    }
    return true;
}

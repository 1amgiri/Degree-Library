<?php
// api/send_custom_email.php
require_once 'db.php';
require_once 'mail_helper.php';

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null || !isset($data['emails']) || !isset($data['subject']) || !isset($data['body'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'emails, subject, and body are required.']);
    exit;
}

$emails = $data['emails'];
$subject = $data['subject'];
$body = $data['body'];

if (!is_array($emails) || empty($emails)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'emails must be a non-empty array.']);
    exit;
}

$successCount = 0;
$failCount = 0;
$failedEmails = [];

foreach ($emails as $email) {
    $email = trim($email);
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $unsubscribe_url = "https://degreelibrary.gt.tc/updates";
        $footer = "<br><hr><p style='font-size:11px;color:#888;text-align:center;'>You are receiving this because you subscribed to updates on Free Degree Library.<br><a href='{$unsubscribe_url}'>Manage Preferences or Unsubscribe</a></p>";
        $full_body = $body . $footer;
        
        if (send_html_email($email, $subject, $full_body)) {
            $successCount++;
        } else {
            $failCount++;
            $failedEmails[] = $email;
        }
    } else {
        $failCount++;
        $failedEmails[] = $email;
    }
}

echo json_encode([
    'status' => 'success',
    'success_count' => $successCount,
    'fail_count' => $failCount,
    'failed_emails' => $failedEmails
]);

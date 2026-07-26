<?php
// api/test_mail.php
header('Content-Type: text/plain');
header('Cache-Control: no-cache, must-revalidate');

require_once 'mail_helper.php';

echo "=== Free Degree Library PHPMailer Diagnostic Tool ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "Testing email dispatch to: cirravosolutions@gmail.com\n\n";

echo "1. Checking PHPMailer Availability...\n";
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "   - Composer autoloader found: YES\n";
} else {
    echo "   - Composer autoloader found: NO\n";
}

if (
    file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php') || 
    file_exists(__DIR__ . '/PHPMailer/PHPMailer.php') || 
    file_exists(__DIR__ . '/phpmailer/src/PHPMailer.php') || 
    file_exists(__DIR__ . '/phpmailer/PHPMailer.php') || 
    file_exists(__DIR__ . '/PHPMailer-FE_v4.11/src/PHPMailer.php')
) {
    echo "   - Standalone PHPMailer installation directory found: YES\n";
} else {
    echo "   - Standalone PHPMailer installation directory found: NO\n";
}

$test_email = 'cirravosolutions@gmail.com';
$test_subject = '🔔 Free Degree Library PHPMailer Diagnostic Test Alert';
$test_body = '<h2>This is a diagnostic test email.</h2><p>If you receive this, your PHPMailer SMTP configuration is working properly!</p>';

echo "\n2. Triggering send_html_email()...\n";
$result = send_html_email($test_email, $test_subject, $test_body);

echo "\n3. Result: " . ($result ? "SUCCESS!" : "FAILED (Check logs below or verify Gmail App Password configuration)") . "\n";

// Display the diagnostic log
echo "\n--- Recent Debug Log (mail_debug.log) ---\n";
if (file_exists(__DIR__ . '/mail_debug.log')) {
    echo file_get_contents(__DIR__ . '/mail_debug.log');
} else {
    echo "(No log entries found. If the test failed, check folder write permissions.)\n";
}

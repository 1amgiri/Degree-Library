<?php
// api/db.php
// Ensure we use the old error reporting mode (returns false instead of throwing exceptions)
// This is critical for PHP 8.1+ compatibility with the current codebase's error handling.
mysqli_report(MYSQLI_REPORT_OFF);

if (file_exists(__DIR__ . '/secrets.php')) {
    require_once __DIR__ . '/secrets.php';
} elseif (file_exists(__DIR__ . '/secrets.example.php')) {
    require_once __DIR__ . '/secrets.example.php';
}

$host = getenv('DB_HOST') ?: (defined('CFG_DB_HOST') ? CFG_DB_HOST : 'localhost');
$user = getenv('DB_USER') ?: (defined('CFG_DB_USER') ? CFG_DB_USER : 'db_user');
$pass = getenv('DB_PASS') ?: (defined('CFG_DB_PASS') ? CFG_DB_PASS : 'db_password');
$db_name = getenv('DB_NAME') ?: (defined('CFG_DB_NAME') ? CFG_DB_NAME : 'db_name');

try {
    $conn = new mysqli($host, $user, $pass, $db_name);

    if ($conn->connect_error) {
        header('Content-Type: application/json');
        die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]));
    }

    // Set charset to utf8mb4 for emoji support
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    header('Content-Type: application/json');
    die(json_encode(['status' => 'error', 'message' => 'Database exception: ' . $e->getMessage()]));
}

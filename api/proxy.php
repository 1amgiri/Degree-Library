<?php
/**
 * proxy.php
 * This script acts as a gateway for all API requests. 
 * It bypasses "Browser Integrity Checks" often found on free hosting like InfinityFree.
 */

// Avoid forcing JSON content type if we are requesting download.php
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
if ($endpoint !== 'download.php') {
    header('Content-Type: application/json; charset=utf-8');
}
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Enable CORS if needed (though on same domain it's usually fine)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Prevent PHP Warnings/Errors from outputting HTML/text
ini_set('display_errors', 0);
error_reporting(E_ALL);

set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity) || ($severity & (E_NOTICE | E_WARNING | E_DEPRECATED | E_USER_NOTICE | E_USER_DEPRECATED))) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    // Validation: Ensure endpoint is provided and safe
    if (!$endpoint || !preg_match('/^[a-z0-9_]+\.php$/', $endpoint)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing API endpoint provided to proxy.']);
        exit;
    }

    $filePath = __DIR__ . '/' . $endpoint;

    if (file_exists($filePath)) {
        require $filePath;
    } else {
        header("HTTP/1.1 404 Not Found");
        echo json_encode(['status' => 'error', 'message' => "API endpoint '$endpoint' not found on server."]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

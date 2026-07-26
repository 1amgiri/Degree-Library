<?php
// api/settings_update.php
require_once 'db.php';

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null || !isset($data['emails_enabled'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'emails_enabled value is required.']);
    exit;
}

$emails_enabled_val = $data['emails_enabled'] ? '1' : '0';

// Ensure site_settings table exists
$createTableQuery = "CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(255) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
)";
if (!$conn->query($createTableQuery)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to initialize settings table: ' . $conn->error]);
    exit;
}

// Upsert query for emails_enabled
$stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('emails_enabled', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
if ($stmt) {
    $stmt->bind_param("ss", $emails_enabled_val, $emails_enabled_val);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Settings updated successfully.']);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Query preparation failed: ' . $conn->error]);
}

$conn->close();

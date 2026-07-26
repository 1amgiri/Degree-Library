<?php
// api/settings_get.php
require_once 'db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Ensure site_settings table exists
$createTableQuery = "CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(255) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
)";
if (!$conn->query($createTableQuery)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to initialize settings table: ' . $conn->error]);
    exit;
}

// Fetch emails_enabled setting
$stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'emails_enabled'");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $emails_enabled = ($row['setting_value'] === '1' || $row['setting_value'] === 'true');
        echo json_encode(['status' => 'success', 'emails_enabled' => $emails_enabled]);
    } else {
        // Seeding the initial setting if it does not exist
        $insertStmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('emails_enabled', '1')");
        if ($insertStmt) {
            $insertStmt->execute();
            $insertStmt->close();
        }
        echo json_encode(['status' => 'success', 'emails_enabled' => true]);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Query preparation failed: ' . $conn->error]);
}

$conn->close();

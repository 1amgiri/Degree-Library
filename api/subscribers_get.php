<?php
// api/subscribers_get.php
require_once 'db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Ensure enabled column exists in subscriptions
$table_check = @$conn->query("SHOW COLUMNS FROM subscriptions LIKE 'enabled'");
if ($table_check && $table_check->num_rows === 0) {
    @$conn->query("ALTER TABLE subscriptions ADD COLUMN enabled TINYINT(1) DEFAULT 1");
}

$query = "SELECT * FROM subscriptions ORDER BY created_at DESC";
$result = @$conn->query($query);

$subscribers = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $subscribers[] = [
            'id' => isset($row['id']) ? (int)$row['id'] : 0,
            'email' => isset($row['email']) ? $row['email'] : 'N/A',
            'notify_announcements' => isset($row['notify_announcements']) ? (bool)$row['notify_announcements'] : true,
            'notify_community' => isset($row['notify_community']) ? (bool)$row['notify_community'] : true,
            'notify_materials' => isset($row['notify_materials']) ? (bool)$row['notify_materials'] : true,
            'enabled' => isset($row['enabled']) ? (bool)$row['enabled'] : true,
            'created_at' => isset($row['created_at']) ? $row['created_at'] : ''
        ];
    }
    echo json_encode(['status' => 'success', 'subscribers' => $subscribers]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch subscribers: ' . $conn->error]);
}

$conn->close();

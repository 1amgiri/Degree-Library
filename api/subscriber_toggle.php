<?php
// api/subscriber_toggle.php
require_once 'db.php';

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null || !isset($data['email']) || !isset($data['enabled'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'email and enabled values are required.']);
    exit;
}

$email = trim($data['email']);
$enabled = $data['enabled'] ? 1 : 0;

$stmt = $conn->prepare("UPDATE subscriptions SET enabled = ? WHERE email = ?");
if ($stmt) {
    $stmt->bind_param("is", $enabled, $email);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Subscriber status updated successfully.']);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Query preparation failed: ' . $conn->error]);
}

$conn->close();

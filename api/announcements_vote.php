<?php
// api/announcements_vote.php
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['option_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Missing option_id.']);
    exit;
}

$option_id = $data['option_id'];

$stmt = $conn->prepare("UPDATE poll_options SET votes = votes + 1 WHERE id = ?");
$stmt->bind_param("i", $option_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['status' => 'error', 'message' => 'Vote failed.']);
}

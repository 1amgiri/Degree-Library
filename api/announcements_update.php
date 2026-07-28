<?php
// api/announcements_update.php
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Missing ID.']);
    exit;
}

$id = $data['id'];
$name = $data['name'];
$description = $data['description'];
$type = $data['type'];
$link_url = $data['link_url'];
$poll_results_public = $data['poll_results_public'] ? 1 : 0;

$stmt = $conn->prepare("UPDATE announcements SET name = ?, description = ?, type = ?, link_url = ?, poll_results_public = ? WHERE id = ?");
$stmt->bind_param("ssssii", $name, $description, $type, $link_url, $poll_results_public, $id);

if ($stmt->execute()) {
    // Note: We don't allow changing the poll options or whether it has a poll during an update for simplicity
    $result = $conn->query("SELECT * FROM announcements WHERE id = $id");
    $ann = $result->fetch_assoc();
    $ann['has_poll'] = (bool)$ann['has_poll'];
    $ann['poll_results_public'] = (bool)$ann['poll_results_public'];
    
    $opts = [];
    if($ann['has_poll']) {
        $optRes = $conn->query("SELECT * FROM poll_options WHERE announcement_id = $id");
        while($or = $optRes->fetch_assoc()) $opts[] = ['id' => (int)$or['id'], 'text' => $or['text'], 'votes' => (int)$or['votes']];
    }
    $ann['poll_options'] = $opts;

    echo json_encode(['status' => 'success', 'data' => $ann]);
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['status' => 'error', 'message' => 'Update failed.']);
}

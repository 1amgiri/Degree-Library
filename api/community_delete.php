<?php
// community_delete.php
header('Content-Type: application/json');
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

$id = $data['id'];

// First, find the image path to delete it from disk if it exists
$stmt = $conn->prepare("SELECT image_path FROM community_posts WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();

if ($post && $post['image_path']) {
    $file_path = '../' . $post['image_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

$stmt = $conn->prepare("DELETE FROM community_posts WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['status' => 'error', 'message' => 'Deletion failed.']);
}

$stmt->close();
$conn->close();

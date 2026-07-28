<?php
// api/comments_add.php
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['material_id']) || !isset($data['user_name']) || !isset($data['comment'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

$material_id = $data['material_id'];
$user_name = $data['user_name'];
$comment = $data['comment'];

$stmt = $conn->prepare("INSERT INTO comments (material_id, user_name, comment) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $material_id, $user_name, $comment);

if ($stmt->execute()) {
    $commentId = $stmt->insert_id;
    $result = $conn->query("SELECT * FROM comments WHERE id = $commentId");
    $newComment = $result->fetch_assoc();
    
    echo json_encode(['status' => 'success', 'data' => $newComment]);
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['status' => 'error', 'message' => 'Failed to add comment.']);
}

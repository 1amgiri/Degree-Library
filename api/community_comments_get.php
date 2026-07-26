<?php
// community_comments_get.php
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_GET['post_id'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Post ID is required."]);
    exit;
}

$post_id = intval($_GET['post_id']);

try {
    $stmt = $conn->prepare("SELECT id, post_id, user_name, comment, created_at FROM community_comments WHERE post_id = ? ORDER BY created_at ASC");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $post_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $comments = [];
    
    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $row['post_id'] = (int)$row['post_id'];
        $comments[] = $row;
    }
    
    $stmt->close();
    echo json_encode($comments);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();

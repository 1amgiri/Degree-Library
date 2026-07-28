<?php
// community_like.php
header('Content-Type: application/json');
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['post_id']) || !isset($data['user_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

$post_id = (int)$data['post_id'];
$user_id = $data['user_id'];

// Start transaction to safely toggle the like
$conn->begin_transaction();

try {
    // Check if the like already exists
    $stmt = $conn->prepare("SELECT id FROM community_likes WHERE post_id = ? AND user_id = ?");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param("is", $post_id, $user_id);
    if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
    
    $result = $stmt->get_result();
    $likeExists = $result->num_rows > 0;
    $stmt->close();

    $is_liked = false;

    if ($likeExists) {
        // Unlike
        $stmt_del = $conn->prepare("DELETE FROM community_likes WHERE post_id = ? AND user_id = ?");
        $stmt_del->bind_param("is", $post_id, $user_id);
        $stmt_del->execute();
        $stmt_del->close();
        $is_liked = false;
    } else {
        // Like
        $stmt_ins = $conn->prepare("INSERT INTO community_likes (post_id, user_id) VALUES (?, ?)");
        $stmt_ins->bind_param("is", $post_id, $user_id);
        $stmt_ins->execute();
        $stmt_ins->close();
        $is_liked = true;
    }

    // Get the new likes count
    $stmt_count = $conn->prepare("SELECT COUNT(*) as likes_count FROM community_likes WHERE post_id = ?");
    $stmt_count->bind_param("i", $post_id);
    $stmt_count->execute();
    $countResult = $stmt_count->get_result();
    $countRow = $countResult->fetch_assoc();
    $likes_count = (int)$countRow['likes_count'];
    $stmt_count->close();

    $conn->commit();
    echo json_encode([
        'status' => 'success', 
        'data' => [
            'is_liked' => $is_liked,
            'likes_count' => $likes_count
        ]
    ]);
} catch (Exception $e) {
    $conn->rollback();
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();

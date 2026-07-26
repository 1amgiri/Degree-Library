<?php
// community_vote.php
header('Content-Type: application/json');
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['post_id']) || !isset($data['option_id']) || !isset($data['user_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

$post_id = $data['post_id'];
$option_id = $data['option_id'];
$user_id = $data['user_id'];

$conn->begin_transaction();

try {
    // Check if user already voted
    $stmt_check = $conn->prepare("SELECT id FROM community_poll_votes WHERE post_id = ? AND user_id = ?");
    $stmt_check->bind_param("is", $post_id, $user_id);
    $stmt_check->execute();
    $checkResult = $stmt_check->get_result();
    if ($checkResult->fetch_assoc()) {
        throw new Exception("You have already voted on this poll.");
    }
    $stmt_check->close();
    
    // Record vote
    $stmt_vote = $conn->prepare("INSERT INTO community_poll_votes (post_id, option_id, user_id) VALUES (?, ?, ?)");
    $stmt_vote->bind_param("iis", $post_id, $option_id, $user_id);
    $stmt_vote->execute();
    $stmt_vote->close();
    
    // Increment vote count
    $stmt_inc = $conn->prepare("UPDATE community_poll_options SET votes = votes + 1 WHERE id = ?");
    $stmt_inc->bind_param("i", $option_id);
    $stmt_inc->execute();
    $stmt_inc->close();
    
    $conn->commit();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    $conn->rollback();
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();

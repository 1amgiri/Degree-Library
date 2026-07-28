<?php
// community_get.php
header('Content-Type: application/json');
require_once 'db.php';

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';

$query = "
    SELECT p.*, 
           COALESCE(l.likes_count, 0) as likes_count, 
           COALESCE(l.has_liked, 0) as has_liked,
           COALESCE(c.comments_count, 0) as comments_count
    FROM community_posts p 
    LEFT JOIN (
        SELECT post_id, 
               COUNT(id) as likes_count, 
               MAX(CASE WHEN user_id = ? THEN 1 ELSE 0 END) as has_liked
        FROM community_likes
        GROUP BY post_id
    ) l ON p.id = l.post_id
    LEFT JOIN (
        SELECT post_id, 
               COUNT(id) as comments_count
        FROM community_comments
        GROUP BY post_id
    ) c ON p.id = c.post_id
    ORDER BY p.created_at DESC
";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $posts = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int)$row['id'];
            $row['is_admin'] = (bool)$row['is_admin'];
            $row['has_poll'] = (bool)$row['has_poll'];
            $row['likes_count'] = (int)$row['likes_count'];
            $row['has_liked'] = (bool)$row['has_liked'];
            $row['comments_count'] = (int)$row['comments_count'];
            
            $postId = $row['id'];
        if ($row['has_poll']) {
            $stmt_poll = $conn->prepare("SELECT * FROM community_poll_options WHERE post_id = ?");
            if ($stmt_poll) {
                $stmt_poll->bind_param("i", $postId);
                $stmt_poll->execute();
            $pollResult = $stmt_poll->get_result();
            
            $pollOptions = [];
            while ($optRow = $pollResult->fetch_assoc()) {
                $pollOptions[] = [
                    'id' => (int)$optRow['id'],
                    'text' => $optRow['text'],
                    'votes' => (int)$optRow['votes']
                ];
            }
            $row['poll_options'] = $pollOptions;
            $stmt_poll->close();
            }
        }
        $posts[] = $row;
    }
}
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare query: ' . $conn->error]);
    $conn->close();
    exit;
}

echo json_encode($posts);
$conn->close();

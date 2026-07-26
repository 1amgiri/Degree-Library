<?php
// community_comments_add.php
header('Content-Type: application/json');
require_once 'db.php';
@$conn->query("ALTER TABLE community_comments MODIFY COLUMN user_name VARCHAR(2000)");

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['post_id']) || !isset($data['user_name']) || !isset($data['comment']) || trim($data['user_name']) === '' || trim($data['comment']) === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or incomplete data']);
    exit;
}

$post_id = (int)$data['post_id'];
$user_name = trim($data['user_name']);
$comment_text = htmlspecialchars(trim($data['comment']));

$blueTick = '<svg width="16" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-left: 4px;"><path d="M12 2.2c-.3 0-.6.1-.8.2l-2.4 1.4c-.2.1-.4.2-.7.2H6.3c-.5 0-.9.4-.9.9v1.8c0 .2-.1.5-.2.7L3.8 9.6c-.2.2-.3.4-.3.7 0 .2.1.5.3.7l1.4 2.4c.1.2.2.4.2.7v1.8c0 .5.4.9.9.9h1.8c.2 0 .5.1.7.2l2.4 1.4c.2.2.4.3.7.3.3 0 .5-.1.7-.3l2.4-1.4c.2-.1.4-.2.7-.2h1.8c.5 0 .9-.4.9-.9v-1.8c0-.2.1-.5.2-.7l1.4-2.4c.2-.2.3-.4.3-.7 0-.3-.1-.5-.3-.7l-1.4-2.4c-.1-.2-.2-.4-.2-.7V6.3c0-.5-.4-.9-.9-.9h-1.8c-.2 0-.5-.1-.7-.2L13.5 2.4c-.2-.1-.4-.2-.7-.2H12z" fill="#0095f6"/><path d="M10.8 15.6c-.2 0-.4-.1-.5-.2l-2.6-2.6c-.3-.3-.3-.8 0-1.1.3-.3.8-.3 1.1 0l2 2 5.2-5.2c.3-.3.8-.3 1.1 0 .3.3.3.8 0 1.1l-5.8 5.8c-.1.2-.3.2-.5.2z" fill="#fff"/></svg>';
$goldTick = '<svg width="16" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-left: 4px;"><path d="M12 2.2c-.3 0-.6.1-.8.2l-2.4 1.4c-.2.1-.4.2-.7.2H6.3c-.5 0-.9.4-.9.9v1.8c0 .2-.1.5-.2.7L3.8 9.6c-.2.2-.3.4-.3.7 0 .2.1.5.3.7l1.4 2.4c.1.2.2.4.2.7v1.8c0 .5.4.9.9.9h1.8c.2 0 .5.1.7.2l2.4 1.4c.2.2.4.3.7.3.3 0 .5-.1.7-.3l2.4-1.4c.2-.1.4-.2.7-.2h1.8c.5 0 .9-.4.9-.9v-1.8c0-.2.1-.5.2-.7l1.4-2.4c.2-.2.3-.4.3-.7 0-.3-.1-.5-.3-.7l-1.4-2.4c-.1-.2-.2-.4-.2-.7V6.3c0-.5-.4-.9-.9-.9h-1.8c-.2 0-.5-.1-.7-.2L13.5 2.4c-.2-.1-.4-.2-.7-.2H12z" fill="#ffcf00"/><path d="M10.8 15.6c-.2 0-.4-.1-.5-.2l-2.6-2.6c-.3-.3-.3-.8 0-1.1.3-.3.8-.3 1.1 0l2 2 5.2-5.2c.3-.3.8-.3 1.1 0 .3.3.3.8 0 1.1l-5.8 5.8c-.1.2-.3.2-.5.2z" fill="#fff"/></svg>';

if (file_exists(__DIR__ . '/secrets.php')) { require_once __DIR__ . '/secrets.php'; } elseif (file_exists(__DIR__ . '/secrets.example.php')) { require_once __DIR__ . '/secrets.example.php'; }
$admin1 = defined('CFG_ADMIN_KEY_1') && CFG_ADMIN_KEY_1 !== '20606787' ? CFG_ADMIN_KEY_1 : 'yadavGIRI@4153';
$admin2 = defined('CFG_ADMIN_KEY_2') ? CFG_ADMIN_KEY_2 : 'bca@cirravo';
if ($user_name === $admin2 || $user_name === 'bca@cirravo') {
    $user_name = 'Sethu - Admin <span style="font-size: 10px; background: #eee; padding: 2px 4px; border-radius: 4px; margin-left: 4px; color: #333;">BCAhub</span>' . $blueTick;
} elseif ($user_name === $admin1 || $user_name === 'yadavGIRI@4153') {
    $user_name = 'Giri-Admin <span style="font-size: 10px; background: #eee; padding: 2px 4px; border-radius: 4px; margin-left: 4px; color: #333;">Free Degree Library</span>' . $blueTick;
} else {
    $user_name = htmlspecialchars($user_name);
}

try {
    $stmt = $conn->prepare("INSERT INTO community_comments (post_id, user_name, comment) VALUES (?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("iss", $post_id, $user_name, $comment_text);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $last_id = $stmt->insert_id;
    $stmt->close();
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'id' => $last_id,
            'post_id' => $post_id,
            'user_name' => $user_name,
            'comment' => $comment_text,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();

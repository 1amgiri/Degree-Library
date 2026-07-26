<?php
// community_add.php
header('Content-Type: application/json');
require_once 'db.php';
@$conn->query("ALTER TABLE community_posts MODIFY COLUMN name VARCHAR(2000)");

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['content']) || !isset($data['name'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

// Lazy migration for allow_html
@$conn->query("ALTER TABLE community_posts ADD COLUMN allow_html TINYINT(1) DEFAULT 0");

$blueTick = '<svg width="16" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-left: 4px;"><path d="M12 2.2c-.3 0-.6.1-.8.2l-2.4 1.4c-.2.1-.4.2-.7.2H6.3c-.5 0-.9.4-.9.9v1.8c0 .2-.1.5-.2.7L3.8 9.6c-.2.2-.3.4-.3.7 0 .2.1.5.3.7l1.4 2.4c.1.2.2.4.2.7v1.8c0 .5.4.9.9.9h1.8c.2 0 .5.1.7.2l2.4 1.4c.2.2.4.3.7.3.3 0 .5-.1.7-.3l2.4-1.4c.2-.1.4-.2.7-.2h1.8c.5 0 .9-.4.9-.9v-1.8c0-.2.1-.5.2-.7l1.4-2.4c.2-.2.3-.4.3-.7 0-.3-.1-.5-.3-.7l-1.4-2.4c-.1-.2-.2-.4-.2-.7V6.3c0-.5-.4-.9-.9-.9h-1.8c-.2 0-.5-.1-.7-.2L13.5 2.4c-.2-.1-.4-.2-.7-.2H12z" fill="#0095f6"/><path d="M10.8 15.6c-.2 0-.4-.1-.5-.2l-2.6-2.6c-.3-.3-.3-.8 0-1.1.3-.3.8-.3 1.1 0l2 2 5.2-5.2c.3-.3.8-.3 1.1 0 .3.3.3.8 0 1.1l-5.8 5.8c-.1.2-.3.2-.5.2z" fill="#fff"/></svg>';
$goldTick = '<svg width="16" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-left: 4px;"><path d="M12 2.2c-.3 0-.6.1-.8.2l-2.4 1.4c-.2.1-.4.2-.7.2H6.3c-.5 0-.9.4-.9.9v1.8c0 .2-.1.5-.2.7L3.8 9.6c-.2.2-.3.4-.3.7 0 .2.1.5.3.7l1.4 2.4c.1.2.2.4.2.7v1.8c0 .5.4.9.9.9h1.8c.2 0 .5.1.7.2l2.4 1.4c.2.2.4.3.7.3.3 0 .5-.1.7-.3l2.4-1.4c.2-.1.4-.2.7-.2h1.8c.5 0 .9-.4.9-.9v-1.8c0-.2.1-.5.2-.7l1.4-2.4c.2-.2.3-.4.3-.7 0-.3-.1-.5-.3-.7l-1.4-2.4c-.1-.2-.2-.4-.2-.7V6.3c0-.5-.4-.9-.9-.9h-1.8c-.2 0-.5-.1-.7-.2L13.5 2.4c-.2-.1-.4-.2-.7-.2H12z" fill="#ffcf00"/><path d="M10.8 15.6c-.2 0-.4-.1-.5-.2l-2.6-2.6c-.3-.3-.3-.8 0-1.1.3-.3.8-.3 1.1 0l2 2 5.2-5.2c.3-.3.8-.3 1.1 0 .3.3.3.8 0 1.1l-5.8 5.8c-.1.2-.3.2-.5.2z" fill="#fff"/></svg>';

$user_id = $data['user_id'] ?? '';
$name = trim($data['name']);
$content = $data['content'];
$has_poll = isset($data['has_poll']) && $data['has_poll'] ? 1 : 0;
$image_path = null;

if (file_exists(__DIR__ . '/secrets.php')) { require_once __DIR__ . '/secrets.php'; } elseif (file_exists(__DIR__ . '/secrets.example.php')) { require_once __DIR__ . '/secrets.example.php'; }
$admin1 = defined('CFG_ADMIN_KEY_1') && CFG_ADMIN_KEY_1 !== '20606787' ? CFG_ADMIN_KEY_1 : 'yadavGIRI@4153';
$admin2 = defined('CFG_ADMIN_KEY_2') ? CFG_ADMIN_KEY_2 : 'bca@cirravo';
if ($name === $admin2 || $name === 'bca@cirravo') {
    $name = 'Sethu - Admin <span style="font-size: 10px; background: #eee; padding: 2px 4px; border-radius: 4px; margin-left: 4px; color: #333;">BCAhub</span>' . $blueTick;
    $is_admin = 1;
    $allow_html = isset($data['allow_html']) && $data['allow_html'] ? 1 : 0;
} elseif ($name === $admin1 || $name === 'yadavGIRI@4153') {
    $name = 'Giri-Admin <span style="font-size: 10px; background: #eee; padding: 2px 4px; border-radius: 4px; margin-left: 4px; color: #333;">Free Degree Library</span>' . $blueTick;
    $is_admin = 1;
    $allow_html = isset($data['allow_html']) && $data['allow_html'] ? 1 : 0;
} else {
    $is_admin = 0;
    $allow_html = 0;
    $name = htmlspecialchars($name);
}

if (isset($data['image']) && $data['image'] !== null) {
    try {
        $image_data = $data['image'];
        $fileType = strtolower(explode('/', $image_data['type'])[1]);
        $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        
        if (!in_array($fileType, $allowed_extensions)) {
            throw new Exception("Invalid image format.");
        }
        
        $image_name = uniqid() . '.' . $fileType;
        
        $parts = explode(',', $image_data['content']);
        $content_base64 = count($parts) > 1 ? $parts[1] : $parts[0];
        $decoded_image = base64_decode($content_base64);
        
        $upload_dir = '../uploads/community/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_path = $upload_dir . $image_name;
        if (file_put_contents($file_path, $decoded_image) !== false) {
            $image_path = 'uploads/community/' . $image_name;
        }
    } catch (Exception $e) {
        // Silently fail on image upload error for now
    }
}

// Start transaction manually for mysqli
$conn->begin_transaction();

try {
    // Check if slug column exists in community_posts
    $slug_exists = false;
    $colRes = $conn->query("SHOW COLUMNS FROM community_posts LIKE 'slug'");
    if ($colRes && $colRes->num_rows > 0) {
        $slug_exists = true;
    }

    $slug = '';
    if ($slug_exists) {
        // Generate unique slug from community post content
        $text = strip_tags($content);
        if (strlen($text) > 60) {
            $text = substr($text, 0, 60);
            $lastSpace = strrpos($text, ' ');
            if ($lastSpace !== false && $lastSpace > 0) {
                $text = substr($text, 0, $lastSpace);
            }
        }
        
        $base_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
        $base_slug = preg_replace('/-+/', '-', $base_slug);
        $base_slug = trim($base_slug, '-') ?: 'post';

        $slug = $base_slug;
        $counter = 2;
        while (true) {
            $stmt_slug = $conn->prepare("SELECT id FROM community_posts WHERE slug = ?");
            if ($stmt_slug) {
                $stmt_slug->bind_param("s", $slug);
                $stmt_slug->execute();
                $res_slug = $stmt_slug->get_result();
                if ($res_slug->num_rows === 0) {
                    break;
                }
                $stmt_slug->close();
            } else {
                break; // Break if query fails
            }
            $slug = $base_slug . '-' . $counter;
            $counter++;
        }
    }

    if ($slug_exists) {
        $stmt = $conn->prepare("INSERT INTO community_posts (user_id, name, content, image_path, is_admin, has_poll, allow_html, slug) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        $stmt->bind_param("ssssiiis", $user_id, $name, $content, $image_path, $is_admin, $has_poll, $allow_html, $slug);
    } else {
        $stmt = $conn->prepare("INSERT INTO community_posts (user_id, name, content, image_path, is_admin, has_poll, allow_html) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        $stmt->bind_param("ssssiii", $user_id, $name, $content, $image_path, $is_admin, $has_poll, $allow_html);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    $post_id = $conn->insert_id;
    $stmt->close();

    if ($has_poll && isset($data['poll_options']) && is_array($data['poll_options'])) {
        $stmt_poll = $conn->prepare("INSERT INTO community_poll_options (post_id, text, votes) VALUES (?, ?, 0)");
        foreach ($data['poll_options'] as $option_text) {
            if (trim($option_text) !== '') {
                $trimmed_opt = trim($option_text);
                $stmt_poll->bind_param("is", $post_id, $trimmed_opt);
                $stmt_poll->execute();
            }
        }
        $stmt_poll->close();
    }
    
    $conn->commit();

    // Trigger email alerts for all subscribers who opted in to notify_community
    try {
        require_once 'mail_helper.php';
        $subject = "[Free Degree Library] New Community Discussion: Question by " . strip_tags($name);
        $body = "<h2 style=\"font-family: 'Courier New', monospace; font-size: 22px; font-weight: bold; color: #4E56FF; margin-top: 0; margin-bottom: 20px;\">💬 New Community Doubt Posted!</h2>";
        $body .= "<p style=\"margin-bottom: 12px;\"><strong>Author:</strong> " . strip_tags($name) . "</p>";
        $body .= "<p style=\"margin-bottom: 12px;\"><strong>Doubt Details:</strong></p>";
        $body .= "<blockquote style='border-left: 4px solid #2D3347; padding-left: 12px; color: #555555; font-style: italic; margin: 15px 0;'>";
        if ($allow_html) {
            $body .= $content;
        } else {
            $body .= nl2br(htmlspecialchars($content));
        }
        $body .= "</blockquote>";
        $body .= "<div style=\"margin-top: 25px; margin-bottom: 15px;\">";
        $body .= "<a href='https://degreelibrary.gt.tc/community.html' style='display: inline-block; padding: 12px 22px; border: 3px solid #2D3347; background: #FFCA28; color: #000000; font-family: \"Courier New\", monospace; font-weight: bold; text-decoration: none;'>Join Discussion</a>";
        $body .= "</div>";
        
        trigger_group_notifications($subject, $body, 'notify_community');
    } catch (Exception $e) {
        // Silently fail mail alerts
    }

    echo json_encode(['status' => 'success', 'id' => $post_id]);
} catch (Exception $e) {
    $conn->rollback();
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();

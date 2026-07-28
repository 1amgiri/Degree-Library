<?php
// api/uploads.php
require_once 'db.php';
@$conn->query("ALTER TABLE uploads MODIFY COLUMN uploader_name VARCHAR(2000)");

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
    exit;
}

$name = $data['name'] ?? '';
$tags = $data['tags'] ?? '';
$category = $data['category'] ?? '';
$uploader = $data['uploader'] ?? '';
$fileData = $data['file'] ?? null;

if (!$name || !$tags || !$category || !$uploader || !$fileData) {
    echo json_encode(['status' => 'error', 'message' => 'All fields (name, tags, category, uploader, file) are required']);
    exit;
}

$blueTick = '<svg width="16" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-left: 4px;"><path d="M12 2.2c-.3 0-.6.1-.8.2l-2.4 1.4c-.2.1-.4.2-.7.2H6.3c-.5 0-.9.4-.9.9v1.8c0 .2-.1.5-.2.7L3.8 9.6c-.2.2-.3.4-.3.7 0 .2.1.5.3.7l1.4 2.4c.1.2.2.4.2.7v1.8c0 .5.4.9.9.9h1.8c.2 0 .5.1.7.2l2.4 1.4c.2.2.4.3.7.3.3 0 .5-.1.7-.3l2.4-1.4c.2-.1.4-.2.7-.2h1.8c.5 0 .9-.4.9-.9v-1.8c0-.2.1-.5.2-.7l1.4-2.4c.2-.2.3-.4.3-.7 0-.3-.1-.5-.3-.7l-1.4-2.4c-.1-.2-.2-.4-.2-.7V6.3c0-.5-.4-.9-.9-.9h-1.8c-.2 0-.5-.1-.7-.2L13.5 2.4c-.2-.1-.4-.2-.7-.2H12z" fill="#0095f6"/><path d="M10.8 15.6c-.2 0-.4-.1-.5-.2l-2.6-2.6c-.3-.3-.3-.8 0-1.1.3-.3.8-.3 1.1 0l2 2 5.2-5.2c.3-.3.8-.3 1.1 0 .3.3.3.8 0 1.1l-5.8 5.8c-.1.2-.3.2-.5.2z" fill="#fff"/></svg>';
$goldTick = '<svg width="16" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-left: 4px;"><path d="M12 2.2c-.3 0-.6.1-.8.2l-2.4 1.4c-.2.1-.4.2-.7.2H6.3c-.5 0-.9.4-.9.9v1.8c0 .2-.1.5-.2.7L3.8 9.6c-.2.2-.3.4-.3.7 0 .2.1.5.3.7l1.4 2.4c.1.2.2.4.2.7v1.8c0 .5.4.9.9.9h1.8c.2 0 .5.1.7.2l2.4 1.4c.2.2.4.3.7.3.3 0 .5-.1.7-.3l2.4-1.4c.2-.1.4-.2.7-.2h1.8c.5 0 .9-.4.9-.9v-1.8c0-.2.1-.5.2-.7l1.4-2.4c.2-.2.3-.4.3-.7 0-.3-.1-.5-.3-.7l-1.4-2.4c-.1-.2-.2-.4-.2-.7V6.3c0-.5-.4-.9-.9-.9h-1.8c-.2 0-.5-.1-.7-.2L13.5 2.4c-.2-.1-.4-.2-.7-.2H12z" fill="#ffcf00"/><path d="M10.8 15.6c-.2 0-.4-.1-.5-.2l-2.6-2.6c-.3-.3-.3-.8 0-1.1.3-.3.8-.3 1.1 0l2 2 5.2-5.2c.3-.3.8-.3 1.1 0 .3.3.3.8 0 1.1l-5.8 5.8c-.1.2-.3.2-.5.2z" fill="#fff"/></svg>';

$uploader = trim($uploader);
if (file_exists(__DIR__ . '/secrets.php')) { require_once __DIR__ . '/secrets.php'; } elseif (file_exists(__DIR__ . '/secrets.example.php')) { require_once __DIR__ . '/secrets.example.php'; }
$admin1 = defined('CFG_ADMIN_KEY_1') && CFG_ADMIN_KEY_1 !== '20606787' ? CFG_ADMIN_KEY_1 : 'yadavGIRI@4153';
$admin2 = defined('CFG_ADMIN_KEY_2') ? CFG_ADMIN_KEY_2 : 'bca@cirravo';
if ($uploader === $admin2 || $uploader === 'bca@cirravo') {
    $uploader = 'Sethu - Admin <span style="font-size: 10px; background: #eee; padding: 2px 4px; border-radius: 4px; margin-left: 4px; color: #333;">BCAhub</span>' . $blueTick;
} elseif ($uploader === $admin1 || $uploader === 'yadavGIRI@4153') {
    $uploader = 'Giri-Admin <span style="font-size: 10px; background: #eee; padding: 2px 4px; border-radius: 4px; margin-left: 4px; color: #333;">Free Degree Library</span>' . $blueTick;
} else {
    $uploader = htmlspecialchars($uploader);
}

// Process Base64 file
$fileName = $fileData['name'];
$fileType = $fileData['type'];

$allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'rar', 'png', 'jpg', 'jpeg', 'gif', 'webp'];
$file_extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($file_extension, $allowed_extensions)) {
    echo json_encode(['status' => 'error', 'message' => 'File type not allowed. Please upload a valid document or image.']);
    exit;
}

$base64Content = $fileData['content'];

// Remove data URI prefix if present
if (preg_match('/^data:.*;base64,/', $base64Content)) {
    $base64Content = substr($base64Content, strpos($base64Content, ',') + 1);
}

$decodedContent = base64_decode($base64Content);
if (!$decodedContent) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to decode file content']);
    exit;
}

// Save file
$uploadDir = '../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$safeFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
$filePath = $uploadDir . $safeFileName;

if (file_put_contents($filePath, $decodedContent)) {
    $dbFilePath = 'uploads/' . $safeFileName;
    
    // Dynamically detect table columns to handle different database schemas
    $columns = [];
    $colResult = $conn->query("DESCRIBE materials");
    if ($colResult) {
        while ($colRow = $colResult->fetch_assoc()) {
            $columns[] = strtolower($colRow['Field']);
        }
    }

    $slug = '';
    if (in_array('slug', $columns)) {
        // Generate unique slug from material name
        $base_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $base_slug = preg_replace('/-+/', '-', $base_slug);
        $base_slug = trim($base_slug, '-') ?: 'material';

        $slug = $base_slug;
        $counter = 2;
        while (true) {
            $stmt_slug = $conn->prepare("SELECT id FROM materials WHERE slug = ?");
            if ($stmt_slug) {
                $stmt_slug->bind_param("s", $slug);
                $stmt_slug->execute();
                $res_slug = $stmt_slug->get_result();
                if ($res_slug->num_rows === 0) {
                    break;
                }
                $stmt_slug->close();
            } else {
                break; // If query fails, just break
            }
            $slug = $base_slug . '-' . $counter;
            $counter++;
        }
    }

    $fields = ['name', 'uploader', 'file_name', 'file_type', 'file_path'];
    $params = [$name, $uploader, $fileName, $fileType, $dbFilePath];

    if (in_array('tags', $columns)) {
        $fields[] = 'tags';
        $params[] = $tags;
    }
    if (in_array('category', $columns)) {
        $fields[] = 'category';
        $params[] = $category;
    }
    if (in_array('subject', $columns)) {
        $fields[] = 'subject';
        $params[] = $name;
    }
    if (in_array('group_name', $columns)) {
        $fields[] = 'group_name';
        $params[] = 'BCA (General)';
    }
    if (in_array('semester', $columns)) {
        $fields[] = 'semester';
        // Try parsing semester from tags (e.g. "Sem III", "sem 3", etc.)
        $parsedSem = 'I';
        if (preg_match('/sem\s*([ivx\d]+)/i', $tags, $semMatch)) {
            $parsedSem = strtoupper($semMatch[1]);
        }
        $fields[] = 'semester';
        $params[] = $parsedSem;
    }

    // Since we added 'semester' twice in fields if we did, let's make sure fields/params match.
    // Let's rewrite it cleanly:
    $fields = ['name', 'uploader', 'file_name', 'file_type', 'file_path'];
    $params = [$name, $uploader, $fileName, $fileType, $dbFilePath];

    if (in_array('slug', $columns)) {
        $fields[] = 'slug';
        $params[] = $slug;
    }

    if (in_array('tags', $columns)) {
        $fields[] = 'tags';
        $params[] = $tags;
    }
    if (in_array('category', $columns)) {
        $fields[] = 'category';
        $params[] = $category;
    }
    if (in_array('subject', $columns)) {
        $fields[] = 'subject';
        $params[] = $name;
    }
    if (in_array('group_name', $columns)) {
        $fields[] = 'group_name';
        $params[] = 'BCA (General)';
    }
    if (in_array('semester', $columns)) {
        $fields[] = 'semester';
        // Try to parse semester from tags
        $parsedSem = 'I';
        if (preg_match('/sem\s*([ivx\d]+)/i', $tags, $semMatch)) {
            $parsedSem = strtoupper($semMatch[1]);
        }
        $params[] = $parsedSem;
    }

    $placeholders = array_fill(0, count($fields), '?');
    $types = str_repeat('s', count($fields));

    $sql = "INSERT INTO materials (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            // Trigger email alerts for all subscribers who opted in to notify_materials
            try {
                require_once 'mail_helper.php';
                $subject = "[Free Degree Library] New Material Uploaded: " . $name;
                $body = "<h2 style=\"font-family: 'Courier New', monospace; font-size: 22px; font-weight: bold; color: #4E56FF; margin-top: 0; margin-bottom: 20px;\">📚 New Material Uploaded!</h2>";
                $body .= "<p style=\"margin-bottom: 12px;\"><strong>Title:</strong> " . htmlspecialchars($name) . "</p>";
                $body .= "<p style=\"margin-bottom: 12px;\"><strong>Category:</strong> " . htmlspecialchars($category) . "</p>";
                $body .= "<p style=\"margin-bottom: 12px;\"><strong>Tags:</strong> " . htmlspecialchars($tags) . "</p>";
                $body .= "<p style=\"margin-bottom: 20px;\"><strong>Uploader:</strong> " . htmlspecialchars($uploader) . "</p>";
                $body .= "<div style=\"margin-top: 25px; margin-bottom: 15px;\">";
                $body .= "<a href='https://degreelibrary.gt.tc' style='display: inline-block; padding: 12px 22px; border: 3px solid #2D3347; background: #FFCA28; color: #000000; font-family: \"Courier New\", monospace; font-weight: bold; text-decoration: none;'>View Material</a>";
                $body .= "</div>";
                
                trigger_group_notifications($subject, $body, 'notify_materials');
            } catch (Exception $e) {
                // Silently fail mail alerts
            }

            echo json_encode(['status' => 'success', 'message' => 'Material uploaded successfully', 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database save failed: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Statement preparation failed: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save file to disk']);
}

$conn->close();

<?php
// redirect.php
require_once 'api/db.php';

$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    if ($type === 'material') {
        $stmt = $conn->prepare("SELECT slug FROM materials WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            if (!empty($row['slug'])) {
                header("Location: /material/" . $row['slug'], true, 301);
                exit;
            }
        }
    } elseif ($type === 'post') {
        $stmt = $conn->prepare("SELECT slug FROM community_posts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            if (!empty($row['slug'])) {
                header("Location: /community/" . $row['slug'], true, 301);
                exit;
            }
        }
    }
}

// Fallback to home if not found
header("Location: /", true, 302);
exit;
?>

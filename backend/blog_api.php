<?php
require_once 'config.php';
header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $result = $conn->query("SELECT id, slug, title, category, status, created_at FROM blog_posts ORDER BY created_at DESC");
        $posts = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $posts[] = $row;
            }
        }
        echo json_encode(['status' => 'success', 'data' => $posts]);
    } else if ($action === 'get' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode(['status' => 'success', 'data' => $result->fetch_assoc()]);
        $stmt->close();
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($action === 'save') {
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $slug = $conn->real_escape_string($data['slug']);
        $title = $conn->real_escape_string($data['title']);
        $meta_description = $conn->real_escape_string($data['meta_description']);
        $content = $conn->real_escape_string($data['content']);
        $category = $conn->real_escape_string($data['category']);
        $status = $conn->real_escape_string($data['status']);

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE blog_posts SET slug=?, title=?, meta_description=?, content=?, category=?, status=? WHERE id=?");
            $stmt->bind_param("ssssssi", $slug, $title, $meta_description, $content, $category, $status, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO blog_posts (slug, title, meta_description, content, category, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $slug, $title, $meta_description, $content, $category, $status);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Post saved']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        $stmt->close();
    } else if ($action === 'delete') {
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['status' => 'success']);
        $stmt->close();
    }
}
$conn->close();
?>

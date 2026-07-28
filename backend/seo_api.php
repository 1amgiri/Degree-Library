<?php
require_once 'config.php';
header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $result = $conn->query("SELECT id, url_slug, page_title, schema_type, updated_at FROM seo_pages ORDER BY url_slug ASC");
        $pages = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $pages[] = $row;
            }
        }
        echo json_encode(['status' => 'success', 'data' => $pages]);
    } else if ($action === 'get' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM seo_pages WHERE id = ?");
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
        $url_slug = $conn->real_escape_string($data['url_slug']);
        $page_title = $conn->real_escape_string($data['page_title']);
        $meta_description = $conn->real_escape_string($data['meta_description']);
        $h1_title = $conn->real_escape_string($data['h1_title']);
        $intro_content = $conn->real_escape_string($data['intro_content']);
        $schema_type = $conn->real_escape_string($data['schema_type']);

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE seo_pages SET url_slug=?, page_title=?, meta_description=?, h1_title=?, intro_content=?, schema_type=? WHERE id=?");
            $stmt->bind_param("ssssssi", $url_slug, $page_title, $meta_description, $h1_title, $intro_content, $schema_type, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO seo_pages (url_slug, page_title, meta_description, h1_title, intro_content, schema_type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $url_slug, $page_title, $meta_description, $h1_title, $intro_content, $schema_type);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'SEO Page saved']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        $stmt->close();
    } else if ($action === 'delete') {
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $stmt = $conn->prepare("DELETE FROM seo_pages WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['status' => 'success']);
        $stmt->close();
    }
}
$conn->close();
?>

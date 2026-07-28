<?php
// api/comments_get.php
require_once 'db.php';

$material_id = isset($_GET['material_id']) ? (int)$_GET['material_id'] : 0;

if (!$material_id) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Missing material_id.']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM comments WHERE material_id = ? ORDER BY created_at DESC");
if ($stmt) {
    $stmt->bind_param("i", $material_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = false;
}

$comments = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($comments);

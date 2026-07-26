<?php
// api/carousel_update.php
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id']) || !isset($data['alt_text'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
    exit;
}

$id = $data['id'];
$alt_text = $data['alt_text'];
$link_url = isset($data['link_url']) ? $data['link_url'] : '';

$updateImage = isset($data['image']);
$filePath = null;

if ($updateImage) {
    $image = $data['image'];
    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $fileType = strtolower(explode('/', $image['type'])[1]);
    $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
    
    if (!in_array($fileType, $allowed_extensions)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['status' => 'error', 'message' => 'Invalid image format.']);
        exit;
    }
    
    $fileName = uniqid() . '.' . $fileType;
    $filePath = 'uploads/' . $fileName;
    $base64Content = $image['content'];
    if (preg_match('/^data:.*;base64,/', $base64Content)) {
        $base64Content = substr($base64Content, strpos($base64Content, ',') + 1);
    }
    $fileData = base64_decode($base64Content);
    file_put_contents($uploadDir . $fileName, $fileData);
}

if ($updateImage) {
    $stmt = $conn->prepare("UPDATE carousel_slides SET image_path = ?, alt_text = ?, link_url = ? WHERE id = ?");
    $stmt->bind_param("sssi", $filePath, $alt_text, $link_url, $id);
} else {
    $stmt = $conn->prepare("UPDATE carousel_slides SET alt_text = ?, link_url = ? WHERE id = ?");
    $stmt->bind_param("ssi", $alt_text, $link_url, $id);
}

if ($stmt->execute()) {
    $result = $conn->query("SELECT * FROM carousel_slides WHERE id = $id");
    $updatedSlide = $result->fetch_assoc();
    echo json_encode(['status' => 'success', 'data' => $updatedSlide]);
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['status' => 'error', 'message' => 'Failed to update slide.']);
}

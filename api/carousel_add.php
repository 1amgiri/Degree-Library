<?php
// api/carousel_add.php
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['image']) || !isset($data['alt_text'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
    exit;
}

$image = $data['image'];
$alt_text = $data['alt_text'];
$link_url = isset($data['link_url']) ? $data['link_url'] : '';

// Handle Image Upload
$uploadDir = '../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

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
if (file_put_contents($uploadDir . $fileName, $fileData)) {
    $stmt = $conn->prepare("INSERT INTO carousel_slides (image_path, alt_text, link_url) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $filePath, $alt_text, $link_url);
    
    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $result = $conn->query("SELECT * FROM carousel_slides WHERE id = $newId");
        $newSlide = $result->fetch_assoc();
        
        echo json_encode(['status' => 'success', 'data' => $newSlide]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['status' => 'error', 'message' => 'Failed to save slide to database.']);
    }
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['status' => 'error', 'message' => 'Failed to save image file.']);
}

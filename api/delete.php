<?php
// api/delete.php
require_once 'db.php';

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$id = $data['id'] ?? 0;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Material ID is required']);
    exit;
}

// Get file path first to delete the file from disk
$stmt = $conn->prepare("SELECT file_path FROM materials WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$material = $result->fetch_assoc();
$stmt->close();

if ($material) {
    $dbPath = $material['file_path'];
    $cleanPath = str_replace('\\', '/', $dbPath);
    $cleanPath = preg_replace('#\.\.+/#', '', $cleanPath);
    $cleanPath = ltrim($cleanPath, '/');
    
    // If it doesn't start with uploads/, prepend uploads/
    if (stripos($cleanPath, 'uploads/') !== 0) {
        $cleanPath = 'uploads/' . $cleanPath;
    }
    
    $fullPath = dirname(__DIR__) . '/' . $cleanPath;
    if (file_exists($fullPath) && is_file($fullPath)) {
        unlink($fullPath);
    }
    
    $stmt = $conn->prepare("DELETE FROM materials WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Material deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database deletion failed: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Material not found']);
}

$conn->close();

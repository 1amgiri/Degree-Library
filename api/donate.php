<?php
require 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : null;
    $note = isset($_POST['note']) ? trim($_POST['note']) : null;
    
    // Only insert if at least one field is provided
    if (!empty($name) || !empty($note)) {
        try {
            // Auto-create table to ensure it exists
            $conn->query("CREATE TABLE IF NOT EXISTS donors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) DEFAULT NULL,
                note TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            $stmt = $conn->prepare("INSERT INTO donors (name, note) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $note);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error', 'msg' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'Nothing to save']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>

<?php
require '../config.php';

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['image']) && isset($data['alt_text'])) {
        $uploadDir = '../carousel_uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $alt_text = $data['alt_text'];
        $link_url = isset($data['link_url']) && !empty($data['link_url']) ? $data['link_url'] : null;

        $fileInfo = $data['image'];
        $originalFileName = $fileInfo['name'];
        $base64Content = $fileInfo['content'];

        list($type, $base64Data) = explode(';', $base64Content);
        list(, $base64Data)      = explode(',', $base64Data);
        $fileData = base64_decode($base64Data);

        $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        $file_extension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $response['message'] = 'Invalid image format.';
            echo json_encode($response);
            exit;
        }

        $uniqueFileName = 'slide_' . time() . '_' . uniqid() . '.' . $file_extension;
        $targetFilePath = $uploadDir . $uniqueFileName;

        if (file_put_contents($targetFilePath, $fileData)) {
            $stmt = $conn->prepare("INSERT INTO carousel_slides (image_path, link_url, alt_text) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $targetFilePath, $link_url, $alt_text);

            if ($stmt->execute()) {
                $new_id = $stmt->insert_id;
                $response['status'] = 'success';
                $response['message'] = 'Slide added successfully.';
                // Return the new slide object so the frontend can update its state
                $response['data'] = [
                    'id' => $new_id,
                    'image_path' => 'https://' . $_SERVER['HTTP_HOST'] . '/' . str_replace('../', '', $targetFilePath),
                    'link_url' => $link_url,
                    'alt_text' => $alt_text,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            } else {
                $response['message'] = 'Database insert failed: ' . $stmt->error;
                unlink($targetFilePath);
            }
            $stmt->close();
        } else {
            $response['message'] = 'Failed to save the image file.';
        }
    } else {
        $response['message'] = 'Required data (image, alt_text) is missing.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);
?>

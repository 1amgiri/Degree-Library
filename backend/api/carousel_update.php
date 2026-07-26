<?php
require '../config.php';

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = isset($data['id']) ? intval($data['id']) : 0;

    if ($id > 0 && isset($data['alt_text'])) {
        $alt_text = $data['alt_text'];
        $link_url = isset($data['link_url']) && !empty($data['link_url']) ? $data['link_url'] : null;

        $image_path = null;
        // Check if a new image is being uploaded
        if (isset($data['image'])) {
            // First, get the old image path to delete it
            $stmt_select = $conn->prepare("SELECT image_path FROM carousel_slides WHERE id = ?");
            $stmt_select->bind_param("i", $id);
            $stmt_select->execute();
            $result_select = $stmt_select->get_result();
            $row = $result_select->fetch_assoc();
            if ($row && file_exists($row['image_path'])) {
                unlink($row['image_path']);
            }
            $stmt_select->close();

            // Now, handle the new image upload
            $uploadDir = '../carousel_uploads/';
            $fileInfo = $data['image'];
            $originalFileName = $fileInfo['name'];
            $base64Content = $fileInfo['content'];
            list(, $base64Data) = explode(',', $base64Content);
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
            file_put_contents($targetFilePath, $fileData);
            $image_path = $targetFilePath;
        }

        // Prepare the SQL statement for updating
        if ($image_path) {
            $stmt_update = $conn->prepare("UPDATE carousel_slides SET alt_text = ?, link_url = ?, image_path = ? WHERE id = ?");
            $stmt_update->bind_param("sssi", $alt_text, $link_url, $image_path, $id);
        } else {
            $stmt_update = $conn->prepare("UPDATE carousel_slides SET alt_text = ?, link_url = ? WHERE id = ?");
            $stmt_update->bind_param("ssi", $alt_text, $link_url, $id);
        }

        if ($stmt_update->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Slide updated successfully.';
             // Fetch the updated slide to return
            $stmt_fetch = $conn->prepare("SELECT id, image_path, link_url, alt_text, created_at FROM carousel_slides WHERE id = ?");
            $stmt_fetch->bind_param("i", $id);
            $stmt_fetch->execute();
            $updated_slide = $stmt_fetch->get_result()->fetch_assoc();
            $updated_slide['image_path'] = 'https://' . $_SERVER['HTTP_HOST'] . '/' . str_replace('../', '', $updated_slide['image_path']);
            $response['data'] = $updated_slide;
            $stmt_fetch->close();

        } else {
            $response['message'] = 'Database update failed: ' . $stmt_update->error;
        }
        $stmt_update->close();
    } else {
        $response['message'] = 'Invalid or missing data.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);
?>

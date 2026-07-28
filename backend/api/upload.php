<?php
require '../config.php';

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the JSON payload from the request
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['file']) && isset($data['name'])) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $name = $data['name'];
        $subject = $data['subject'];
        $group = $data['group'];
        $semester = $data['semester'];
        $uploader = $data['uploader'];
        
        // File details from the JSON payload
        $fileInfo = $data['file'];
        $originalFileName = $fileInfo['name'];
        $fileType = $fileInfo['type'];
        
        $allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'rar', 'png', 'jpg', 'jpeg', 'gif', 'webp'];
        $file_extension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $response['message'] = 'File type not allowed. Please upload a valid document or image.';
            echo json_encode($response);
            exit;
        }

        $base64Content = $fileInfo['content'];

        // Decode the Base64 string
        // The format is "data:image/png;base64,iVBORw0KGgo..."
        // We need to strip the prefix to get the pure Base64 data.
        list($type, $base64Data) = explode(';', $base64Content);
        list(, $base64Data)      = explode(',', $base64Data);
        $fileData = base64_decode($base64Data);

        // Create a unique filename to prevent overwrites
        $uniqueFileName = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9-_\.]/', '', $originalFileName);
        $targetFilePath = $uploadDir . $uniqueFileName;
        
        // Save the file to the uploads directory
        if (file_put_contents($targetFilePath, $fileData)) {
            $stmt = $conn->prepare("INSERT INTO materials (name, subject, group_name, semester, uploader, file_name, file_type, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $name, $subject, $group, $semester, $uploader, $originalFileName, $fileType, $targetFilePath);

            if ($stmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'File uploaded successfully.';
            } else {
                $response['message'] = 'Database insert failed: ' . $stmt->error;
                unlink($targetFilePath); // Clean up file if DB insert fails
            }
            $stmt->close();
        } else {
            $response['message'] = 'Sorry, there was an error saving your file.';
        }
    } else {
        $response['message'] = 'Required fields or file data are missing in the request.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);
?>

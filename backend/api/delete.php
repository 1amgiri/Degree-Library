<?php
require '../config.php';

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the ID from the POST body
    $data = json_decode(file_get_contents('php://input'), true);
    $id = isset($data['id']) ? intval($data['id']) : 0;

    if ($id > 0) {
        // First, get the file path to delete the actual file
        $stmt = $conn->prepare("SELECT file_path FROM materials WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            $filePath = $row['file_path'];
            // Delete the file from the server
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Now, delete the record from the database
            $deleteStmt = $conn->prepare("DELETE FROM materials WHERE id = ?");
            $deleteStmt->bind_param("i", $id);
            if ($deleteStmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Material deleted successfully.';
            } else {
                $response['message'] = 'Failed to delete record from database.';
            }
            $deleteStmt->close();
        } else {
             $response['message'] = 'Material not found.';
        }
        $stmt->close();
    } else {
        $response['message'] = 'Invalid material ID provided.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);
?>

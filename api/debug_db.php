<?php
// api/debug_db.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once 'db.php';

    $diagnostics = [];

    // 1. Check Table Structure
    $schema = [];
    $result = $conn->query("DESCRIBE materials");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $schema[] = $row;
        }
        $diagnostics['schema'] = $schema;
    } else {
        $diagnostics['schema_error'] = $conn->error;
    }

    // 2. Check Rows and File Existence
    $rows = [];
    $result = $conn->query("SELECT * FROM materials ORDER BY id ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rawPath = $row['file_path'];
            // Normalize path relative to api/ directory
            if (strpos($rawPath, '../') === 0) {
                $rawPath = substr($rawPath, 3);
            }
            $filePath = '../' . $rawPath;
            $file_exists = @is_file($filePath);
            $size = $file_exists ? @filesize($filePath) : false;

            $row['debug'] = [
                'resolved_filePath' => $filePath,
                'file_exists' => $file_exists,
                'file_size_bytes' => $size
            ];
            $rows[] = $row;
        }
        $diagnostics['records'] = $rows;
    } else {
        $diagnostics['records_error'] = $conn->error;
    }

    // 3. Scan Uploads Folder
    $uploads = [];
    $uploadDir = '../uploads';
    if (is_dir($uploadDir)) {
        $files = scandir($uploadDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $uploads[] = [
                    'filename' => $file,
                    'size' => filesize($uploadDir . '/' . $file)
                ];
            }
        }
        $diagnostics['physical_uploads'] = $uploads;
    } else {
        $diagnostics['uploads_dir_error'] = "Directory $uploadDir does not exist.";
    }

    echo json_encode($diagnostics, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

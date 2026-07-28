<?php
// api/download.php
// Disable error displaying to prevent corrupting download file stream
ini_set('display_errors', 0);
error_reporting(E_ALL);

function find_file_recursive($dir, $filename) {
    if (!is_dir($dir)) {
        return false;
    }
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    foreach (new RecursiveIteratorIterator($it) as $file) {
        if ($file->isFile() && $file->getFilename() === $filename) {
            return $file->getRealPath();
        }
    }
    return false;
}

try {
    require_once 'db.php';

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $pathParam = isset($_GET['path']) ? $_GET['path'] : '';

    $fileName = '';
    $fileType = '';
    $filePath = '';
    $dbPathValue = '';
    $resolvedPath = '';

    if ($id > 0) {
        $stmt = $conn->prepare("SELECT file_name, file_type, file_path FROM materials WHERE id = ?");
        if (!$stmt) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Database statement preparation failed.']);
            exit;
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Material record not found.']);
            $stmt->close();
            $conn->close();
            exit;
        }

        $row = $result->fetch_assoc();
        $fileName = $row['file_name'];
        $fileType = $row['file_type'];
        $dbPathValue = $row['file_path'];

        $stmt->close();
        $conn->close();

        // Normalize the database path
        $cleanPath = str_replace('\\', '/', $dbPathValue);
        $cleanPath = preg_replace('#\.\.+/#', '', $cleanPath);
        $cleanPath = ltrim($cleanPath, '/');

        // If path doesn't start with uploads/, prepend uploads/
        if (stripos($cleanPath, 'uploads/') !== 0) {
            $cleanPath = 'uploads/' . $cleanPath;
        }

        // Target path is relative to the parent of the api/ directory
        $filePath = dirname(__DIR__) . '/' . $cleanPath;
        $resolvedPath = (file_exists($filePath) && is_file($filePath)) ? $filePath : false;
        
        $uploadsDir = dirname(__DIR__) . '/uploads';
        $realUploadsDir = file_exists($uploadsDir) ? realpath($uploadsDir) : false;

        // Search recursively if file is not found immediately and uploads dir exists
        if (!$resolvedPath && $realUploadsDir) {
            $searchedFilename = basename($filePath);
            $foundPath = find_file_recursive($realUploadsDir, $searchedFilename);
            if ($foundPath !== false) {
                $resolvedPath = $foundPath;
            }
        }
    } elseif (!empty($pathParam)) {
        // Path-based download (used for ICET or other static allowed files)
        $cleanPath = str_replace('\\', '/', $pathParam);
        $cleanPath = preg_replace('#\.\.+/#', '', $cleanPath);
        $cleanPath = ltrim($cleanPath, '/');
        $filePath = dirname(__DIR__) . '/' . $cleanPath;
        $fileName = basename($filePath);
        $resolvedPath = (file_exists($filePath) && is_file($filePath)) ? $filePath : false;
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'No download identifier or path specified.']);
        exit;
    }

    // Security check: restrict downloads to allowed directories only
    $isAllowed = false;
    $uploadsDir = dirname(__DIR__) . '/uploads';
    $icetDir = dirname(__DIR__) . '/ICET';
    $allowedDirs = [
        file_exists($uploadsDir) ? realpath($uploadsDir) : false,
        file_exists($icetDir) ? realpath($icetDir) : false
    ];

    if ($resolvedPath && is_file($resolvedPath)) {
        $realResolvedPath = realpath($resolvedPath);
        if ($realResolvedPath !== false) {
            foreach ($allowedDirs as $allowedDir) {
                if ($allowedDir && strpos($realResolvedPath, $allowedDir) === 0) {
                    $isAllowed = true;
                    break;
                }
            }
        }
    }

    if (!$isAllowed) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error', 
            'message' => 'Access restricted or file does not exist.',
            'debug' => [
                'file_path_db' => $dbPathValue,
                'final_resolved_path' => $resolvedPath ?: $filePath,
                'file_exists' => file_exists($resolvedPath ?: $filePath),
                'is_file' => is_file($resolvedPath ?: $filePath)
            ]
        ]);
        exit;
    }

    // Clear output buffer to avoid corruption
    while (ob_get_level()) {
        ob_end_clean();
    }

    if (empty($fileName)) {
        $fileName = basename($resolvedPath);
    }

    // Disable execution time limit for large files
    set_time_limit(0);

    // Serve file with download headers
    header('Content-Description: File Transfer');
    header('Content-Type: ' . (!empty($fileType) ? $fileType : 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . str_replace('"', '\\"', $fileName) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($resolvedPath));

    // Read and output the file in chunks to prevent memory issues with large files
    $handle = fopen($resolvedPath, 'rb');
    if ($handle !== false) {
        while (!feof($handle) && connection_status() === 0) {
            echo fread($handle, 8192); // 8KB chunks
            flush();
        }
        fclose($handle);
    }
    exit;

} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

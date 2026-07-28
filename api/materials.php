<?php
// api/materials.php

// Force JSON headers and disable caching
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Prevent PHP Warnings/Errors from outputting HTML/text
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (!function_exists('materials_error_handler')) {
    function materials_error_handler($severity, $message, $file, $line)
    {
        if (!(error_reporting() & $severity) || ($severity & (E_NOTICE | E_WARNING | E_DEPRECATED | E_USER_NOTICE | E_USER_DEPRECATED))) {
            return false;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
}
set_error_handler('materials_error_handler');

try {
    require_once 'db.php';

    $query_param = isset($_GET['query']) ? $_GET['query'] : '';
    $category = isset($_GET['category']) ? $_GET['category'] : '';

    $sql = "SELECT * FROM materials WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($category)) {
        $sql .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
    }

    if (!empty($query_param)) {
        // Split query into individual words for better matching (AND logic)
        $words = explode(" ", preg_replace('/\s+/', ' ', trim($query_param)));
        foreach ($words as $word) {
            $searchTerm = "%" . $word . "%";
            $sql .= " AND (name LIKE ? OR tags LIKE ? OR uploader LIKE ? OR category LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ssss";
        }
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $materials = [];
        while ($row = $result->fetch_assoc()) {
            $rawPath = $row['file_path'];
            if (strpos($rawPath, '../') === 0) {
                $rawPath = substr($rawPath, 3);
            }
            $row['file_path'] = $rawPath; // Always return the clean, relative-to-root web path
            $filePath = '../' . $rawPath;
            $fileSize = 'Unknown';
            try {
                if (!empty($rawPath) && @is_file($filePath)) {
                    $sizeInBytes = @filesize($filePath);
                    if ($sizeInBytes !== false) {
                        if ($sizeInBytes >= 1048576) {
                            $fileSize = round($sizeInBytes / 1048576, 2) . ' MB';
                        } elseif ($sizeInBytes >= 1024) {
                            $fileSize = round($sizeInBytes / 1024, 2) . ' KB';
                        } else {
                            $fileSize = $sizeInBytes . ' B';
                        }
                    }
                }
            } catch (Exception $fsError) {
                // Ignore filesystem exceptions
            } catch (Throwable $fsError) {
                // Ignore filesystem throwables
            }
            $row['file_size'] = $fileSize;
            $materials[] = $row;
        }

        echo json_encode($materials);
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Query preparation failed: ' . $conn->error]);
    }

    if (isset($conn) && $conn) {
        $conn->close();
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

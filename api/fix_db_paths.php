<?php
// api/fix_db_paths.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once 'db.php';

    $report = [];

    // 1. Fix materials table (file_path)
    $stmt = $conn->query("SELECT id, file_path FROM materials WHERE file_path LIKE '%..%'");
    if ($stmt) {
        $count = 0;
        while ($row = $stmt->fetch_assoc()) {
            $id = $row['id'];
            $oldPath = $row['file_path'];
            
            $newPath = str_replace('\\', '/', $oldPath);
            $newPath = preg_replace('#\.\.+/#', '', $newPath);
            $newPath = ltrim($newPath, '/');
            if (stripos($newPath, 'uploads/') !== 0) {
                $newPath = 'uploads/' . $newPath;
            }

            if ($oldPath !== $newPath) {
                $updateStmt = $conn->prepare("UPDATE materials SET file_path = ? WHERE id = ?");
                $updateStmt->bind_param("si", $newPath, $id);
                if ($updateStmt->execute()) {
                    $count++;
                }
                $updateStmt->close();
            }
        }
        $report['materials_updated'] = $count;
    } else {
        $report['materials_error'] = $conn->error;
    }

    // 2. Fix carousel_slides table (image_path)
    $tableCheck = $conn->query("SHOW TABLES LIKE 'carousel_slides'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $stmt = $conn->query("SELECT id, image_path FROM carousel_slides WHERE image_path LIKE '%..%'");
        if ($stmt) {
            $count = 0;
            while ($row = $stmt->fetch_assoc()) {
                $id = $row['id'];
                $oldPath = $row['image_path'];
                
                $newPath = str_replace('\\', '/', $oldPath);
                $newPath = preg_replace('#\.\.+/#', '', $newPath);
                $newPath = ltrim($newPath, '/');
                if (stripos($newPath, 'uploads/') !== 0) {
                    $newPath = 'uploads/' . $newPath;
                }

                if ($oldPath !== $newPath) {
                    $updateStmt = $conn->prepare("UPDATE carousel_slides SET image_path = ? WHERE id = ?");
                    $updateStmt->bind_param("si", $newPath, $id);
                    if ($updateStmt->execute()) {
                        $count++;
                    }
                    $updateStmt->close();
                }
            }
            $report['carousel_slides_updated'] = $count;
        }
    }

    // 3. Fix community_posts table (image_path)
    $tableCheck = $conn->query("SHOW TABLES LIKE 'community_posts'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $stmt = $conn->query("SELECT id, image_path FROM community_posts WHERE image_path LIKE '%..%'");
        if ($stmt) {
            $count = 0;
            while ($row = $stmt->fetch_assoc()) {
                $id = $row['id'];
                $oldPath = $row['image_path'];
                
                $newPath = str_replace('\\', '/', $oldPath);
                $newPath = preg_replace('#\.\.+/#', '', $newPath);
                $newPath = ltrim($newPath, '/');
                if (stripos($newPath, 'uploads/') !== 0) {
                    $newPath = 'uploads/' . $newPath;
                }

                if ($oldPath !== $newPath) {
                    $updateStmt = $conn->prepare("UPDATE community_posts SET image_path = ? WHERE id = ?");
                    $updateStmt->bind_param("si", $newPath, $id);
                    if ($updateStmt->execute()) {
                        $count++;
                    }
                    $updateStmt->close();
                }
            }
            $report['community_posts_updated'] = $count;
        }
    }

    echo json_encode(['status' => 'success', 'report' => $report], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

<?php
// api/fix_emoji.php
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $tables = [
        'materials', 
        'comments', 
        'community_posts', 
        'community_comments', 
        'community_poll_options', 
        'announcements'
    ];
    
    $results = [];
    foreach ($tables as $table) {
        $sql = "ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if ($conn->query($sql)) {
            $results[$table] = "Successfully converted to utf8mb4";
        } else {
            $results[$table] = "Failed: " . $conn->error;
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Database migration completed',
        'details' => $results
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

if (isset($conn) && $conn) {
    $conn->close();
}

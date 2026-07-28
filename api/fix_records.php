<?php
// api/fix_records.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once 'db.php';

    $sqlDumpPath = '../backend/if0_40025118_sdhr (1).sql';
    if (!file_exists($sqlDumpPath)) {
        echo json_encode(['status' => 'error', 'message' => "SQL dump file not found at $sqlDumpPath"]);
        exit;
    }

    // 1. Inspect schema to see what columns exist in 'materials'
    $columns = [];
    $result = $conn->query("DESCRIBE materials");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = strtolower($row['Field']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to DESCRIBE materials: ' . $conn->error]);
        exit;
    }

    $hasTags = in_array('tags', $columns);
    $hasCategory = in_array('category', $columns);
    $hasSubject = in_array('subject', $columns);
    $hasGroupName = in_array('group_name', $columns);
    $hasSemester = in_array('semester', $columns);

    $dumpContent = file_get_contents($sqlDumpPath);
    
    // Find the INSERT INTO `materials` statement
    preg_match_all("/INSERT INTO `?materials`?[^;]+;/i", $dumpContent, $matches);
    
    $restoredCount = 0;
    $errors = [];
    $details = [];

    foreach ($matches[0] as $insertStmt) {
        if (preg_match("/VALUES\s*(.*)/is", $insertStmt, $valMatch)) {
            $valuesStr = rtrim(trim($valMatch[1]), ';');
            
            $pos = 0;
            $len = strlen($valuesStr);
            while ($pos < $len) {
                $startPos = strpos($valuesStr, '(', $pos);
                if ($startPos === false) break;
                
                $endPos = -1;
                $inString = false;
                for ($i = $startPos + 1; $i < $len; $i++) {
                    $char = $valuesStr[$i];
                    if ($char === "'" && ($i === 0 || $valuesStr[$i-1] !== '\\')) {
                        $inString = !$inString;
                    }
                    if ($char === ')' && !$inString) {
                        $endPos = $i;
                        break;
                    }
                }
                
                if ($endPos === -1) break;
                
                $rowStr = substr($valuesStr, $startPos + 1, $endPos - $startPos - 1);
                $pos = $endPos + 1;
                
                $items = [];
                $currentItem = '';
                $inString = false;
                $itemLen = strlen($rowStr);
                for ($j = 0; $j < $itemLen; $j++) {
                    $c = $rowStr[$j];
                    if ($c === "'" && ($j === 0 || $rowStr[$j-1] !== '\\')) {
                        $inString = !$inString;
                    } elseif ($c === ',' && !$inString) {
                        $items[] = trim($currentItem);
                        $currentItem = '';
                    } else {
                        $currentItem .= $c;
                    }
                }
                $items[] = trim($currentItem);
                
                foreach ($items as &$item) {
                    if (strpos($item, "'") === 0 && substr($item, -1) === "'") {
                        $item = substr($item, 1, -1);
                    }
                    $item = str_replace(["\\'", '\\"'], ["'", '"'], $item);
                }
                
                if (count($items) >= 10) {
                    $id = (int)$items[0];
                    $name = $items[1];
                    $subject = $items[2];
                    $group_name = $items[3];
                    $semester = $items[4];
                    $uploader = $items[5];
                    $file_name = $items[6];
                    $file_type = $items[7];
                    $file_path = $items[8];
                    $created_at = $items[9];
                    
                    // Normalize file path: strip leading '../'
                    if (strpos($file_path, '../') === 0) {
                        $file_path = substr($file_path, 3);
                    }
                    
                    // Check if record exists
                    $checkStmt = $conn->prepare("SELECT id FROM materials WHERE id = ?");
                    $checkStmt->bind_param("i", $id);
                    $checkStmt->execute();
                    $checkStmt->store_result();
                    $exists = $checkStmt->num_rows > 0;
                    $checkStmt->close();
                    
                    if ($exists) {
                        // Build query dynamically depending on schema
                        $fieldsToUpdate = [
                            "name = ?",
                            "uploader = ?",
                            "file_name = ?",
                            "file_type = ?",
                            "file_path = ?",
                            "created_at = ?"
                        ];
                        $params = [$name, $uploader, $file_name, $file_type, $file_path, $created_at];
                        $types = "sssssss"; // we will append type dynamically

                        if ($hasSubject) {
                            $fieldsToUpdate[] = "subject = ?";
                            $params[] = $subject;
                        }
                        if ($hasGroupName) {
                            $fieldsToUpdate[] = "group_name = ?";
                            $params[] = $group_name;
                        }
                        if ($hasSemester) {
                            $fieldsToUpdate[] = "semester = ?";
                            $params[] = $semester;
                        }
                        if ($hasTags) {
                            $fieldsToUpdate[] = "tags = ?";
                            $params[] = "$subject, $group_name, Sem $semester";
                        }
                        if ($hasCategory) {
                            $fieldsToUpdate[] = "category = ?";
                            // Map category based on group name
                            $cat = 'Computers';
                            if (stripos($group_name, 'commerce') !== false || stripos($group_name, 'b.com') !== false) {
                                $cat = 'Business/Commerce';
                            } elseif (stripos($group_name, 'law') !== false) {
                                $cat = 'Law';
                            } elseif (stripos($group_name, 'b.sc') !== false) {
                                $cat = 'Science';
                            }
                            $params[] = $cat;
                        }

                        $params[] = $id; // For WHERE id = ?
                        $types = str_repeat("s", count($params) - 1) . "i";

                        $sql = "UPDATE materials SET " . implode(", ", $fieldsToUpdate) . " WHERE id = ?";
                        $updateStmt = $conn->prepare($sql);
                        if ($updateStmt) {
                            $updateStmt->bind_param($types, ...$params);
                            if ($updateStmt->execute()) {
                                $restoredCount++;
                                $details[] = "Restored ID $id: $name by $uploader ($created_at)";
                            } else {
                                $errors[] = "Failed to update ID $id: " . $updateStmt->error;
                            }
                            $updateStmt->close();
                        } else {
                            $errors[] = "Failed to prepare update for ID $id: " . $conn->error;
                        }
                    }
                }
            }
        }
    }

    echo json_encode([
        'status' => 'success',
        'schema_detected' => [
            'has_tags' => $hasTags,
            'has_category' => $hasCategory,
            'has_subject' => $hasSubject,
            'has_group_name' => $hasGroupName,
            'has_semester' => $hasSemester
        ],
        'restored_records_count' => $restoredCount,
        'details' => $details,
        'errors' => $errors
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

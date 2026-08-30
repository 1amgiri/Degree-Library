<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php';

$latest_material_id = 0;
$latest_post_id = 0;
$latest_reply_id = 0;

$res1 = $conn->query("SELECT MAX(id) as max_id FROM materials");
if ($res1 && $row = $res1->fetch_assoc()) {
    $latest_material_id = (int)$row['max_id'];
}


$res3 = $conn->query("SELECT MAX(id) as max_id FROM community_comments");
if ($res3 && $row = $res3->fetch_assoc()) {
    $latest_reply_id = (int)$row['max_id'];
}

$res2 = $conn->query("SELECT MAX(id) as max_id FROM community_posts");
if ($res2 && $row = $res2->fetch_assoc()) {
    $latest_post_id = (int)$row['max_id'];
}

echo json_encode([
    'status' => 'success',
    'latest_material_id' => $latest_material_id,
    'latest_post_id' => $latest_post_id,
    'latest_reply_id' => $latest_reply_id
]);
exit;

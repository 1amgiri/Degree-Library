<?php
// api/carousel_get.php
require_once 'db.php';

$query = "SELECT * FROM carousel_slides ORDER BY created_at DESC";
$result = $conn->query($query);

$slides = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $slides[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($slides);

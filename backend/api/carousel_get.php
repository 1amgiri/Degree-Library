<?php
require '../config.php';

$sql = "SELECT id, image_path, link_url, alt_text, created_at FROM carousel_slides ORDER BY id ASC";
$result = $conn->query($sql);

$slides = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // IMPORTANT: Make sure the image_path is a full, accessible URL
        $row['image_path'] = 'https://' . $_SERVER['HTTP_HOST'] . '/' . str_replace('../', '', $row['image_path']);
        $slides[] = $row;
    }
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($slides);
?>

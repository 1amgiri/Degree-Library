<?php
require_once 'config.php';
require_once '../api/db.php'; // Reuse existing DB connection

function generate_slug($string)
{
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-') ?: 'item';
}

function generate_slug_from_content($content)
{
    $text = preg_replace('/<(style|script|svg)[^>]*>.*?<\/\1>/is', '', $content);
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
    if (strlen($text) > 60) {
        $text = substr($text, 0, 60);
        $lastSpace = strrpos($text, ' ');
        if ($lastSpace !== false && $lastSpace > 0) {
            $text = substr($text, 0, $lastSpace);
        }
    }
    return generate_slug($text);
}

function get_unique_slug($conn, $table, $base_slug, $id)
{
    $slug = $base_slug;
    $counter = 2;
    $current_slug = $slug;

    while (true) {
        $stmt = $conn->prepare("SELECT id FROM $table WHERE slug = ? AND id != ?");
        $stmt->bind_param("si", $current_slug, $id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            break;
        }
        $current_slug = $slug . '-' . $counter;
        $counter++;
    }
    return $current_slug;
}

// 1. Add slug column to materials
@$conn->query("ALTER TABLE materials ADD COLUMN slug VARCHAR(255) UNIQUE AFTER id");

// 2. Add slug column to community_posts
@$conn->query("ALTER TABLE community_posts ADD COLUMN slug VARCHAR(255) UNIQUE AFTER id");

echo "Updating materials...\n";
$res = $conn->query("SELECT id, name FROM materials WHERE slug IS NULL OR slug = ''");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $base = generate_slug($row['name']);
        $slug = get_unique_slug($conn, 'materials', $base, $row['id']);
        $conn->query("UPDATE materials SET slug = '" . $conn->real_escape_string($slug) . "' WHERE id = " . $row['id']);
    }
}

echo "Updating community posts...\n";
$res = $conn->query("SELECT id, content FROM community_posts");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $base = generate_slug_from_content($row['content']);
        $slug = get_unique_slug($conn, 'community_posts', $base, $row['id']);
        $conn->query("UPDATE community_posts SET slug = '" . $conn->real_escape_string($slug) . "' WHERE id = " . $row['id']);
    }
}

echo "Done generating slugs!\n";
$conn->close();
?>
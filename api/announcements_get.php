<?php
// api/announcements_get.php
require_once 'db.php';

$query = "SELECT * FROM announcements ORDER BY created_at DESC";
$result = $conn->query($query);

$announcements = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $announcementId = $row['id'];
        $row['has_poll'] = (bool)$row['has_poll'];
        $row['poll_results_public'] = (bool)$row['poll_results_public'];
        
        // Fetch poll options if it has a poll
        $pollOptions = [];
        if ($row['has_poll']) {
            $optResult = $conn->query("SELECT * FROM poll_options WHERE announcement_id = $announcementId");
            while ($optRow = $optResult->fetch_assoc()) {
                $pollOptions[] = [
                    'id' => (int)$optRow['id'],
                    'text' => $optRow['text'],
                    'votes' => (int)$optRow['votes']
                ];
            }
        }
        $row['poll_options'] = $pollOptions;
        $announcements[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($announcements);

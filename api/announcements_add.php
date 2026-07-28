<?php
// api/announcements_add.php
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['name']) || !isset($data['description'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

// Only one marquee allowed in DB
$countResult = $conn->query("SELECT COUNT(*) as total FROM announcements");
$countRow = $countResult->fetch_assoc();
if ($countRow && $countRow['total'] > 0) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Only one marquee is allowed. Please delete the existing marquee before creating a new one.']);
    exit;
}

$name = $data['name'];
$description = $data['description'];
$type = $data['type'];
$link_url = $data['link_url'];
$has_poll = $data['has_poll'] ? 1 : 0;
$poll_results_public = $data['poll_results_public'] ? 1 : 0;
$poll_options = isset($data['poll_options']) ? $data['poll_options'] : [];

$stmt = $conn->prepare("INSERT INTO announcements (name, description, type, link_url, has_poll, poll_results_public) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssii", $name, $description, $type, $link_url, $has_poll, $poll_results_public);

if ($stmt->execute()) {
    $announcementId = $stmt->insert_id;
    
    // Add poll options if any
    if ($has_poll && !empty($poll_options)) {
        $optStmt = $conn->prepare("INSERT INTO poll_options (announcement_id, text) VALUES (?, ?)");
        foreach ($poll_options as $optionText) {
            $optStmt->bind_param("is", $announcementId, $optionText);
            $optStmt->execute();
        }
    }
    
    // Fetch and return the new announcement
    $result = $conn->query("SELECT * FROM announcements WHERE id = $announcementId");
    $newAnnouncement = $result->fetch_assoc();
    $newAnnouncement['has_poll'] = (bool)$newAnnouncement['has_poll'];
    $newAnnouncement['poll_results_public'] = (bool)$newAnnouncement['poll_results_public'];
    
    $opts = [];
    if($newAnnouncement['has_poll']) {
        $optRes = $conn->query("SELECT * FROM poll_options WHERE announcement_id = $announcementId");
        while($or = $optRes->fetch_assoc()) $opts[] = ['id' => (int)$or['id'], 'text' => $or['text'], 'votes' => (int)$or['votes']];
    }
    $newAnnouncement['poll_options'] = $opts;

    // Trigger email alerts for all subscribers who opted in to notify_announcements
    try {
        require_once 'mail_helper.php';
        $subject = "[Free Degree Library] New Official Update: " . $name;
        $body = "<h2 style=\"font-family: 'Courier New', monospace; font-size: 22px; font-weight: bold; color: #4E56FF; margin-top: 0; margin-bottom: 20px;\">📣 New Academic Announcement!</h2>";
        $body .= "<p style=\"margin-bottom: 12px;\"><strong>Title:</strong> " . htmlspecialchars($name) . "</p>";
        $body .= "<p style=\"margin-bottom: 12px;\"><strong>Details:</strong> " . nl2br(htmlspecialchars($description)) . "</p>";
        $body .= "<div style=\"margin-top: 25px; margin-bottom: 15px;\">";
        if (!empty($link_url)) {
            $body .= "<a href='{$link_url}' style='display: inline-block; padding: 12px 22px; border: 3px solid #2D3347; background: #29B6F6; color: #FFFFFF; font-family: \"Courier New\", monospace; font-weight: bold; text-decoration: none; margin-bottom: 10px; margin-right: 10px;'>View Attached Link</a>";
        }
        $body .= "<a href='https://degreelibrary.gt.tc' style='display: inline-block; padding: 12px 22px; border: 3px solid #2D3347; background: #FFCA28; color: #000000; font-family: \"Courier New\", monospace; font-weight: bold; text-decoration: none; margin-bottom: 10px;'>Open Dashboard</a>";
        $body .= "</div>";
        
        trigger_group_notifications($subject, $body, 'notify_announcements');
    } catch (Exception $e) {
        // Silently fail mail alerts so the post creation doesn't crash
    }

    echo json_encode(['status' => 'success', 'data' => $newAnnouncement]);
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['status' => 'error', 'message' => 'Failed to create announcement.']);
}

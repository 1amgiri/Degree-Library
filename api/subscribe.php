<?php
// api/subscribe.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    if ($action === 'count') {
        $result = $conn->query("SELECT COUNT(*) as count FROM subscriptions");
        if ($result) {
            $row = $result->fetch_assoc();
            echo json_encode(['status' => 'success', 'count' => (int)$row['count']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database query error: ' . $conn->error]);
        }
        exit;
    }

    $email = isset($_GET['email']) ? trim($_GET['email']) : '';
    if (empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Email is required.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT notify_announcements, notify_community, notify_materials FROM subscriptions WHERE email = ?");
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'status' => 'success',
            'subscribed' => true,
            'preferences' => [
                'announcements' => (bool)$row['notify_announcements'],
                'community' => (bool)$row['notify_community'],
                'materials' => (bool)$row['notify_materials']
            ]
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'subscribed' => false
        ]);
    }
    $stmt->close();
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = isset($input['action']) ? $input['action'] : '';
    $email = isset($input['email']) ? trim($input['email']) : '';

    if (empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Email is required.']);
        exit;
    }

    if ($action === 'subscribe') {
        $notify_announcements = isset($input['notify_announcements']) ? (int)$input['notify_announcements'] : 1;
        $notify_community = isset($input['notify_community']) ? (int)$input['notify_community'] : 1;
        $notify_materials = isset($input['notify_materials']) ? (int)$input['notify_materials'] : 1;

        // Using INSERT ... ON DUPLICATE KEY UPDATE to handle additions and changes gracefully
        $stmt = $conn->prepare("INSERT INTO subscriptions (email, notify_announcements, notify_community, notify_materials) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE notify_announcements = ?, notify_community = ?, notify_materials = ?");
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Database preparation error.']);
            exit;
        }
        $stmt->bind_param("siiiiii", $email, $notify_announcements, $notify_community, $notify_materials, $notify_announcements, $notify_community, $notify_materials);
        
        if ($stmt->execute()) {
            // Send confirmation email
            try {
                require_once 'mail_helper.php';
                $subject = "🎉 Subscribed: Free Degree Library Academic Alerts!";
                $channels = [];
                 if ($notify_announcements) {
                     $channels[] = "📢 <a href='https://degreelibrary.gt.tc/' style='color: #4E56FF; font-weight: bold; text-decoration: none;'>Official Announcements & Polls</a>";
                 }
                 if ($notify_community) {
                     $channels[] = "💬 <a href='https://degreelibrary.gt.tc/community.html' style='color: #4E56FF; font-weight: bold; text-decoration: none;'>Community Doubt-Solving</a>";
                 }
                 if ($notify_materials) {
                     $channels[] = "📚 <a href='https://degreelibrary.gt.tc/' style='color: #4E56FF; font-weight: bold; text-decoration: none;'>Study Materials & PYQs</a>";
                 }
 
                 $body = "<h2 style=\"font-family: 'Courier New', monospace; font-size: 22px; font-weight: bold; color: #4E56FF; margin-top: 0; margin-bottom: 20px;\">🎉 Welcome to Free Degree Library Alerts!</h2>";
                 $body .= "<p style=\"margin-bottom: 15px;\">Thank you for subscribing to <strong>Free Degree Library Alerts</strong>. You will now receive real-time email notifications based on your chosen channels so you never miss critical updates.</p>";
                 $body .= "<p style=\"margin-bottom: 10px;\"><strong>Your Current Preferences:</strong></p>";
                 $body .= "<ul style=\"padding-left: 20px; line-height: 1.8; margin-bottom: 25px;\">";
                 if (empty($channels)) {
                     $body .= "<li style=\"color: #E53935;\">None (You have paused all notifications)</li>";
                 } else {
                     foreach ($channels as $channel) {
                         $body .= "<li style=\"color: #2D3347; margin-bottom: 6px;\">$channel</li>";
                     }
                 }
                $body .= "</ul>";
                $body .= "<div style=\"margin-top: 25px; margin-bottom: 15px;\">";
                $body .= "<a href='https://degreelibrary.gt.tc/' style='display: inline-block; padding: 12px 22px; border: 3px solid #2D3347; background: #FFCA28; color: #000000; font-family: \"Courier New\", monospace; font-weight: bold; text-decoration: none;'>Manage Preferences</a>";
                $body .= "</div>";
 
                 send_html_email($email, $subject, $body);
            } catch (Exception $e) {
                // Silently fail mail alerts so the frontend subscription still completes
            }

            echo json_encode(['status' => 'success', 'message' => 'Subscription updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save subscription: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'unsubscribe') {
        $stmt = $conn->prepare("DELETE FROM subscriptions WHERE email = ?");
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Database preparation error.']);
            exit;
        }
        $stmt->bind_param("s", $email);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Unsubscribed successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to unsubscribe: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
exit;

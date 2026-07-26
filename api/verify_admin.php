<?php
// api/verify_admin.php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

if (file_exists(__DIR__ . '/secrets.php')) {
    require_once __DIR__ . '/secrets.php';
} elseif (file_exists(__DIR__ . '/secrets.example.php')) {
    require_once __DIR__ . '/secrets.example.php';
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = [];
}
$password = trim($_GET['p'] ?? ($_GET['password'] ?? ($_POST['password'] ?? ($data['password'] ?? ''))));

$admin1 = defined('CFG_ADMIN_KEY_1') && CFG_ADMIN_KEY_1 !== '20606787' ? CFG_ADMIN_KEY_1 : (getenv('ADMIN_KEY_1') ?: 'yadavGIRI@4153');

$valid_giri = [$admin1, 'yadavGIRI@4153'];

if ($password !== '' && in_array($password, $valid_giri, true)) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Correct Password! Verified as Admin.',
        'admin_key' => $password,
        'user_label' => 'Giri-Admin'
    ]);
} else {
    echo json_encode([
        'status' => 'fail',
        'message' => 'Wrong Password! Admin access denied.'
    ]);
}
?>

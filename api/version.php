<?php
// api/version.php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo json_encode([
    'version' => '2026.06.20.1782000001',
    'buildTime' => '2026-06-20T17:07:49.000Z',
    'gitCommit' => 'dev-commit'
]);

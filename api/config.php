<?php
// api/config.php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo json_encode([
    'forceReload' => false,
    'minimumSupportedVersion' => '2026.06.19.1781854741'
]);

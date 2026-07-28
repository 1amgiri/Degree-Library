<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  exit(0);
}

if (file_exists(__DIR__ . '/../api/secrets.php')) {
    require_once __DIR__ . '/../api/secrets.php';
}

$servername = defined('CFG_DB_HOST') ? CFG_DB_HOST : "localhost";
$username   = defined('CFG_DB_USER') ? CFG_DB_USER : "db_user";
$password   = defined('CFG_DB_PASS') ? CFG_DB_PASS : "db_password";
$dbname     = defined('CFG_DB_NAME') ? CFG_DB_NAME : "db_name";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>
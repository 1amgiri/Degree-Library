<?php
// api/secrets.example.php - Template for configuring environment secrets
// INSTRUCTIONS: Copy this file to 'api/secrets.php' and insert your database & SMTP credentials.
if (!defined('SECURE_ENV')) {
    define('SECURE_ENV', true);
}

// Database Configuration
define('CFG_DB_HOST', 'your_db_host');
define('CFG_DB_USER', 'your_db_user');
define('CFG_DB_PASS', 'your_db_password');
define('CFG_DB_NAME', 'your_db_name');

// SMTP & Email Newsletter Configuration
define('CFG_SMTP_HOST', 'smtp.gmail.com');
define('CFG_SMTP_USER', 'your-email@gmail.com');
define('CFG_SMTP_PASS', 'your-16-character-app-password');

// Administrator Passwords / Key Tokens
define('CFG_ADMIN_KEY_1', 'yadavGIRI@4153');
?>

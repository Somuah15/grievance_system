<?php
// Get database credentials from environment variable
$db_url = parse_url(getenv('DATABASE_URL'));

define('DB_HOST', $db_url['host']);
define('DB_USER', $db_url['user']);
define('DB_PASS', $db_url['pass']);
define('DB_NAME', ltrim($db_url['path'], '/'));


// Email configuration
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@example.com');
define('SMTP_PASS', 'your_email_password');
define('FROM_EMAIL', 'noreply@yourdomain.com');
define('FROM_NAME', 'ResolverIT System');


// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Set to '1' for debugging
?>
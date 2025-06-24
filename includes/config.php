<?php
session_start();

// Database configuration from environment variables
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));
define('DB_NAME', getenv('DB_NAME'));

// Email configuration from environment variables
define('SMTP_HOST', getenv('SMTP_HOST'));
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER'));
define('SMTP_PASS', getenv('SMTP_PASS'));
define('FROM_EMAIL', getenv('FROM_EMAIL') ?: 'noreply@yourdomain.com');
define('FROM_NAME', getenv('FROM_NAME') ?: 'ResolverIT System');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Error reporting - turn off in production
error_reporting(E_ALL);
ini_set('display_errors', getenv('DISPLAY_ERRORS') ?: 0);
?>
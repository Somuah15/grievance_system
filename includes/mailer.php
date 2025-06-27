<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

// Load .env if not already loaded
if (class_exists('Dotenv\\Dotenv')) {
    $envPath = dirname(__DIR__);
    if (file_exists($envPath . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable($envPath);
        $dotenv->safeLoad();
    } elseif (file_exists($envPath . '/.env.local')) {
        $dotenv = Dotenv\Dotenv::createImmutable($envPath, '.env.local');
        $dotenv->safeLoad();
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_mail($to, $subject, $body, $altBody = '', $attachments = []) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST') ?: 'smtp.example.com';
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USER') ?: 'user@example.com';
        $mail->Password = getenv('SMTP_PASS') ?: 'password';
        $mail->SMTPSecure = getenv('SMTP_SECURE') ?: 'tls';
        $mail->Port = getenv('SMTP_PORT') ?: 587;

        // Sender info
        $from = getenv('MAIL_FROM') ?: 'noreply@example.com';
        $fromName = getenv('MAIL_FROM_NAME') ?: 'ResolverIT';
        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);

        // Attachments
        foreach ($attachments as $file) {
            $mail->addAttachment($file);
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail Error: ' . $mail->ErrorInfo);
        return false;
    }
}
?>
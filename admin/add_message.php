<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $complaint_id = intval($_POST['complaint_id']);
    $message = sanitize_input($_POST['message']);
    
    $stmt = $conn->prepare("INSERT INTO messages (complaint_id, sender_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $complaint_id, $_SESSION['user_id'], $message);
    $stmt->execute();
}

header("Location: view_complaint.php?id=$complaint_id");
exit();
?>
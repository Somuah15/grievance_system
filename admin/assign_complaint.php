<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

require_role('admin');

$complaint_id = intval($_GET['complaint_id']);
$reviewer_id = intval($_GET['reviewer_id']);

// Get complaint title for notification
$stmt = $conn->prepare("SELECT title FROM complaints WHERE id = ?");
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$result = $stmt->get_result();
$complaint = $result->fetch_assoc();

// Assign complaint
$stmt = $conn->prepare("UPDATE complaints SET assigned_to = ?, status = 'assigned' WHERE id = ?");
$stmt->bind_param("ii", $reviewer_id, $complaint_id);
$stmt->execute();

// Send notification to reviewer
send_notification($reviewer_id, "You've been assigned a new complaint: {$complaint['title']}", "reviewer/inbox.php");

// Send email to reviewer
$reviewer_stmt = $conn->prepare("SELECT email, name FROM users WHERE id = ?");
$reviewer_stmt->bind_param("i", $reviewer_id);
$reviewer_stmt->execute();
$reviewer_result = $reviewer_stmt->get_result();
if ($reviewer = $reviewer_result->fetch_assoc()) {
    $to = $reviewer['email'];
    $name = $reviewer['name'];
    $subject = "New Complaint Assigned to You";
    $body = "<p>Dear $name,</p><p>A new complaint has been assigned to you:</p>"
        . "<ul><li><strong>Title:</strong> {$complaint['title']}</li>"
        . "<li><strong>Complaint ID:</strong> $complaint_id</li></ul>"
        . "<p>Please log in to your dashboard to view and respond.</p>";
    send_email_notification($to, $subject, $body);
}

$_SESSION['message'] = "Complaint assigned successfully";
header("Location: complaints.php");
exit();
?>
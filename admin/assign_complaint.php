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

$_SESSION['message'] = "Complaint assigned successfully";
header("Location: complaints.php");
exit();
?>
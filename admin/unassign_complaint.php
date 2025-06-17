<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

require_role('admin');

$complaint_id = intval($_GET['id']);

// Unassign complaint
$stmt = $conn->prepare("UPDATE complaints SET assigned_to = NULL, status = 'new' WHERE id = ?");
$stmt->bind_param("i", $complaint_id);
$stmt->execute();

$_SESSION['message'] = "Complaint unassigned successfully";
header("Location: complaints.php");
exit();
?>
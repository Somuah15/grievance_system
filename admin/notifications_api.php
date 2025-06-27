<?php
// notifications_api.php
session_start();
include '../includes/config.php';
include '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    http_response_code(403);
    exit();
}

// Example: Fetch the count of unresolved complaints as notifications
$countResult = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE status != 'resolved'");
$notification_count = $countResult->fetch_assoc()['count'];

// Example: Fetch the 5 most recent complaint activities
$activityResult = $conn->query("SELECT c.id, c.title, c.status, c.created_at, u.name as reviewer_name
    FROM complaints c
    LEFT JOIN users u ON c.assigned_to = u.id
    ORDER BY c.created_at DESC LIMIT 5");
$activities = [];
while ($row = $activityResult->fetch_assoc()) {
    $activities[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'status' => $row['status'],
        'created_at' => $row['created_at'],
        'reviewer_name' => $row['reviewer_name']
    ];
}

echo json_encode([
    'notification_count' => $notification_count,
    'activities' => $activities
]);

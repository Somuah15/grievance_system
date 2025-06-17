<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

require_login();

// Mark notifications as read when viewing
mark_notifications_read($_SESSION['user_id']);

// Get notifications
$notifications = [];
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - ResolverIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/user.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
   <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --accent-color: #e74c3c;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
        }
        
        .sidebar {
            background-color: var(--dark-color);
            color: white;
            height: 100vh;
            position: fixed;
            width: 250px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            margin-bottom: 0.2rem;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }
        
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            color: white;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card.users {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        
        .stat-card.admins {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }
        
        .stat-card.unfinished {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        
        .stat-card.assigned {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        
        .stat-card.resolved {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }
        
        .stat-card .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .stat-card .stat-label {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .recent-complaints .badge {
            padding: 0.5em 0.75em;
        }
        
        .badge-new {
            background-color: var(--accent-color);
        }
        
        .badge-assigned {
            background-color: var(--primary-color);
        }
        
        .badge-in_progress {
            background-color: #f39c12;
        }
        
        .badge-resolved {
            background-color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">   
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Your Notifications</h2>
    </div>
        <?php if (empty($notifications)): ?>
            <div class="alert alert-info">You have no notifications.</div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($notifications as $notification): ?>
                <a href="<?php echo $notification['link'] ?? '#'; ?>" class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                        <small><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></small>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
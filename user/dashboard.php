<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

// Ensure only users can access
if ($_SESSION['role'] != 'user') {
    header("Location: ../index.php");
    exit();
}

// Get user statistics
$stats = [];
$result = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE user_id = {$_SESSION['user_id']} AND status = 'new'");
$stats['new'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE user_id = {$_SESSION['user_id']} AND (status = 'assigned' OR status = 'in_progress')");
$stats['in_progress'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE user_id = {$_SESSION['user_id']} AND status = 'resolved'");
$stats['resolved'] = $result->fetch_assoc()['count'];

// Get recent complaints
$recent_complaints = [];
$result = $conn->query("SELECT id, title, status, created_at FROM complaints 
                        WHERE user_id = {$_SESSION['user_id']} 
                        ORDER BY created_at DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $recent_complaints[] = $row;
}

// Get unread notifications
$unread_notifications = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM notifications 
                        WHERE user_id = {$_SESSION['user_id']} AND is_read = 0");
$unread_notifications = $result->fetch_assoc()['count'];

// If this file ever handles login directly, add similar send_mail logic as in login.php after successful authentication.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - ResolverIT</title>
     <link rel="icon" type="image/png" href="/assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>User Dashboard</h2>
            <div class="d-flex align-items-center">
                <a href="notifications.php" class="btn btn-primary position-relative me-3">
                    <i class="bi bi-bell"></i>
                    <?php if ($unread_notifications > 0): ?>
                        <span class="notification-badge badge bg-danger rounded-pill"><?php echo $unread_notifications; ?></span>
                    <?php endif; ?>
                </a>
                <div class="text-end">
                    <h5 class="mb-0">Welcome, <?php echo $_SESSION['name']; ?></h5>
                    <small class="text-muted">User</small>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-4 animate-in" style="animation-delay: 0.2s">
                <div class="stat-card new">
                    <div class="stat-value"><?php echo $stats['new']; ?></div>
                    <div class="stat-label">New Complaints</div>
                </div>
            </div>
            <div class="col-md-4 animate-in" style="animation-delay: 0.2s">
                <div class="stat-card in-progress">
                    <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
            </div>
            <div class="col-md-4 animate-in" style="animation-delay: 0.2s">
                <div class="stat-card resolved">
                    <div class="stat-value"><?php echo $stats['resolved']; ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Complaints</h5>
                    <a href="complaints.php" class="btn btn-sm btn-primary">View All</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_complaints as $complaint): ?>
                            <tr>
                                <td><?php echo $complaint['id']; ?></td>
                                <td><?php echo htmlspecialchars($complaint['title']); ?></td>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    switch ($complaint['status']) {
                                        case 'new': $status_class = 'badge-new'; break;
                                        case 'assigned': $status_class = 'badge-assigned'; break;
                                        case 'in_progress': $status_class = 'badge-in_progress'; break;
                                        case 'resolved': $status_class = 'badge-resolved'; break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                                <td>
                                    <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
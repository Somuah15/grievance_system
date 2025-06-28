<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

// Ensure only reviewers can access
if ($_SESSION['role'] != 'reviewer') {
    header("Location: ../index.php");
    exit();
}

// Get reviewer statistics
$stats = [];
$result = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE assigned_to = {$_SESSION['user_id']} AND status = 'new'");
$stats['new'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE assigned_to = {$_SESSION['user_id']} AND (status = 'assigned' OR status = 'in_progress')");
$stats['in_progress'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE assigned_to = {$_SESSION['user_id']} AND status = 'resolved'");
$stats['resolved'] = $result->fetch_assoc()['count'];

// Get recent assigned complaints
$recent_complaints = [];
$result = $conn->query("SELECT c.id, c.title, c.priority, c.status, c.created_at 
                        FROM complaints c 
                        WHERE c.assigned_to = {$_SESSION['user_id']} 
                        ORDER BY c.created_at DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $recent_complaints[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviewer Dashboard - ResolverIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="/assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-speedometer2 me-2"></i>Reviewer Dashboard</h2>
            <div class="d-flex align-items-center">
                <a href="notifications.php" class="btn btn-primary position-relative me-3">
                    <i class="bi bi-bell"></i>
                    <span class="notification-badge badge bg-danger rounded-pill" style="display:none;"></span>
                </a>
                <div class="text-end">
                    <h5 class="mb-0"><i class="bi bi-person-circle me-1"></i>Welcome, <?php echo $_SESSION['name']; ?></h5>
                    <small class="text-muted"><i class="bi bi-person-badge me-1"></i>Reviewer</small>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card new">
                    <i class="bi bi-file-earmark-plus"></i>
                    <div class="stat-value"><?php echo $stats['new']; ?></div>
                    <div class="stat-label">New Complaints</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card in-progress">
                    <i class="bi bi-hourglass-split"></i>
                    <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card resolved">
                    <i class="bi bi-patch-check-fill"></i>
                    <div class="stat-value"><?php echo $stats['resolved']; ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Assigned Complaints</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><i class="bi bi-hash"></i> ID</th>
                                <th><i class="bi bi-card-text"></i> Title</th>
                                <th><i class="bi bi-flag"></i> Priority</th>
                                <th><i class="bi bi-flag"></i> Status</th>
                                <th><i class="bi bi-calendar-event"></i> Date</th>
                                <th><i class="bi bi-eye"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_complaints as $complaint): ?>
                            <tr>
                                <td><?php echo $complaint['id']; ?></td>
                                <td><?php echo htmlspecialchars($complaint['title']); ?></td>
                                <td>
                                    <?php 
                                    $priority_class = '';
                                    $priority_icon = '';
                                    switch ($complaint['priority']) {
                                        case 'high': $priority_class = 'bg-danger'; $priority_icon = 'bi-exclamation-circle-fill'; break;
                                        case 'medium': $priority_class = 'bg-warning text-dark'; $priority_icon = 'bi-exclamation-triangle-fill'; break;
                                        case 'low': $priority_class = 'bg-success'; $priority_icon = 'bi-arrow-down-circle-fill'; break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $priority_class; ?>">
                                        <i class="bi <?php echo $priority_icon; ?> me-1"></i>
                                        <?php echo ucfirst($complaint['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    $status_icon = '';
                                    switch ($complaint['status']) {
                                        case 'new': $status_class = 'badge-new'; $status_icon = 'bi-file-earmark-plus'; break;
                                        case 'assigned': $status_class = 'badge-assigned'; $status_icon = 'bi-person-lines-fill'; break;
                                        case 'in_progress': $status_class = 'badge-in_progress'; $status_icon = 'bi-hourglass-split'; break;
                                        case 'resolved': $status_class = 'badge-resolved'; $status_icon = 'bi-patch-check-fill'; break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <i class="bi <?php echo $status_icon; ?> me-1"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                                <td>
                                    <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-eye"></i> View</a>
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

<?php
// If this file ever handles login directly, add similar send_mail logic as in login.php after successful authentication.
?>
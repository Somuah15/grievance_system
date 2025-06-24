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
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Reviewer Dashboard</h2>
            <div class="text-end">
                <h5>Welcome, <?php echo $_SESSION['name']; ?></h5>
                <small class="text-muted">Reviewer</small>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card new">
                    <div class="stat-value"><?php echo $stats['new']; ?></div>
                    <div class="stat-label">New Complaints</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card in-progress">
                    <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card resolved">
                    <div class="stat-value"><?php echo $stats['resolved']; ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Assigned Complaints</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Priority</th>
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
                                    $priority_class = '';
                                    switch ($complaint['priority']) {
                                        case 'high': $priority_class = 'bg-danger'; break;
                                        case 'medium': $priority_class = 'bg-warning text-dark'; break;
                                        case 'low': $priority_class = 'bg-success'; break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $priority_class; ?>">
                                        <?php echo ucfirst($complaint['priority']); ?>
                                    </span>
                                </td>
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
                                <!-- In the table row where complaints are displayed -->
                                <td>
                                    <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    <a href="checked.php?start=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-success">Start Working</a>
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
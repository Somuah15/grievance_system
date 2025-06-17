<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

// Ensure only reviewers can access
if ($_SESSION['role'] != 'reviewer') {
    header("Location: ../login.php");
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            color: white;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card.new {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        
        .stat-card.in-progress {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        
        .stat-card.resolved {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }
        
        .stat-card .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
        }
    </style>
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
                                <td>
                                    <a href="view_complaint.php" class="btn btn-sm btn-primary">View</a>
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
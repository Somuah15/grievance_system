<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

// Ensure only admins can access
if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Add these headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Get statistics for dashboard
$stats = [];
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user' AND is_active = 1");
$stats['users'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND is_active = 1");
$stats['admins'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE status != 'resolved'");
$stats['unfinished'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'assigned' OR status = 'in_progress'");
$stats['assigned'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'resolved'");
$stats['resolved'] = $result->fetch_assoc()['count'];

// Get recent complaints
$recent_complaints = [];
$result = $conn->query("SELECT c.id, c.title, c.status, c.created_at, u.name as reviewer_name 
                        FROM complaints c 
                        LEFT JOIN users u ON c.assigned_to = u.id 
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
    <title>Admin Dashboard - ResolverIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Dashboard Overview</h1>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-calendar-alt me-2"></i> <?php echo date('F j, Y'); ?>
                </button>
                <button class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-bell me-2"></i> Notifications
                </button>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4 animate-in" style="animation-delay: 0.1s">
                <div class="stat-card users">
                    <div class="stat-value"><?php echo $stats['users']; ?></div>
                    <div class="stat-label">Total Users</div>
                    <i class="fas fa-users position-absolute bottom-0 end-0 me-3 mb-3 opacity-20" style="font-size: 3rem;"></i>
                </div>
            </div>
            <div class="col-md-4 animate-in" style="animation-delay: 0.2s">
                <div class="stat-card admins">
                    <div class="stat-value"><?php echo $stats['admins']; ?></div>
                    <div class="stat-label">Administrators</div>
                    <i class="fas fa-user-shield position-absolute bottom-0 end-0 me-3 mb-3 opacity-20" style="font-size: 3rem;"></i>
                </div>
            </div>
            <div class="col-md-4 animate-in" style="animation-delay: 0.3s">
                <div class="stat-card unfinished">
                    <div class="stat-value"><?php echo $stats['unfinished']; ?></div>
                    <div class="stat-label">Pending Complaints</div>
                    <i class="fas fa-inbox position-absolute bottom-0 end-0 me-3 mb-3 opacity-20" style="font-size: 3rem;"></i>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-6 animate-in" style="animation-delay: 0.4s">
                <div class="stat-card assigned">
                    <div class="stat-value"><?php echo $stats['assigned']; ?></div>
                    <div class="stat-label">Active Cases</div>
                    <i class="fas fa-tasks position-absolute bottom-0 end-0 me-3 mb-3 opacity-20" style="font-size: 3rem;"></i>
                </div>
            </div>
            <div class="col-md-6 animate-in" style="animation-delay: 0.5s">
                <div class="stat-card resolved">
                    <div class="stat-value"><?php echo $stats['resolved']; ?></div>
                    <div class="stat-label">Resolved Cases</div>
                    <i class="fas fa-check-circle position-absolute bottom-0 end-0 me-3 mb-3 opacity-20" style="font-size: 3rem;"></i>
                </div>
            </div>
        </div>
        
        <!-- Recent Complaints -->
        <div class="card animate-in" style="animation-delay: 0.6s">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Complaints</h5>
                <a href="complaints.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-eye me-2"></i> View All
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_complaints as $complaint): ?>
                            <tr>
                                <td>#<?php echo $complaint['id']; ?></td>
                                <td><?php echo htmlspecialchars($complaint['title']); ?></td>
                                <td>
                                    <span class="badge <?php 
                                        echo 'badge-status-' . str_replace('_', '-', $complaint['status']); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo $complaint['reviewer_name'] ? htmlspecialchars($complaint['reviewer_name']) : 'Not assigned'; ?></td>
                                <td><?php echo date('M j, Y', strtotime($complaint['created_at'])); ?></td>
                                <td>
                                    <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card animate-in" style="animation-delay: 0.7s">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3">
                    <a href="create.php?type=user" class="btn btn-primary px-4 py-3 d-flex align-items-center">
                        <i class="fas fa-user-plus me-3" style="font-size: 1.5rem;"></i>
                        <div>
                            <h6 class="mb-0">Create User</h6>
                            <small class="text-white-50">Add new system user</small>
                        </div>
                    </a>
                    <a href="complaints.php" class="btn btn-success px-4 py-3 d-flex align-items-center">
                        <i class="fas fa-tasks me-3" style="font-size: 1.5rem;"></i>
                        <div>
                            <h6 class="mb-0">Manage Complaints</h6>
                            <small class="text-white-50">View all cases</small>
                        </div>
                    </a>
                    <a href="settings.php" class="btn btn-secondary px-4 py-3 d-flex align-items-center">
                        <i class="fas fa-cog me-3" style="font-size: 1.5rem;"></i>
                        <div>
                            <h6 class="mb-0">System Settings</h6>
                            <small class="text-white-50">Configure application</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple animation trigger
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.animate-in');
            elements.forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>
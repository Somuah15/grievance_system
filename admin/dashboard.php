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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --warning-color: #f8961e;
            --info-color: #43aa8b;
            --dark-color: #212529;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fb;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            color: white;
            min-height: 100vh;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
        }
        
        .main-content {
            padding: 2rem;
            margin-left: 250px;
            transition: all 0.3s;
        }
        
        .stat-card {
            border-radius: 16px;
            padding: 2rem 1.5rem 1.5rem 1.5rem;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            border: none;
            transition: none;
        }
        
        .stat-card.users {
            background: linear-gradient(135deg, #4361ee, #3f37c9);
        }
        
        .stat-card.admins {
            background: linear-gradient(135deg, #7209b7, #560bad);
        }
        
        .stat-card.unfinished {
            background: linear-gradient(135deg, #f8961e, #f3722c);
        }
        
        .stat-card.assigned {
            background: linear-gradient(135deg, #43aa8b, #4cc9f0);
        }
        
        .stat-card.resolved {
            background: linear-gradient(135deg, #4cc9f0, #4895ef);
        }
        
        .stat-value {
            font-size: 2.4rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.95;
            font-weight: 500;
        }
        
        .stat-card i {
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
            font-size: 3.5rem;
            opacity: 0.18;
            filter: drop-shadow(0 2px 6px rgba(0,0,0,0.08));
        }
        
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
            padding: 1.25rem 1.5rem;
            border-radius: 16px 16px 0 0 !important;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            font-weight: 600;
            color: #6c757d;
            border-top: none;
            padding: 1rem 1.5rem;
        }
        
        .table td {
            padding: 1rem 1.5rem;
            vertical-align: middle;
        }
        
        .badge-status-new {
            background-color: #e9ecef;
            color: #495057;
        }
        
        .badge-status-assigned {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-status-in-progress {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .badge-status-resolved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .quick-action-btn {
            border-radius: 12px;
            padding: 1.5rem;
            transition: none;
            flex: 1;
            min-width: 250px;
            border: none;
            text-align: left;
            background: #f8f9fa;
            color: #212529;
            box-shadow: 0 1px 4px rgba(0,0,0,0.03);
        }
        
        .quick-action-btn i {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .quick-action-btn h6 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .quick-action-btn small {
            opacity: 0.7;
            font-size: 0.8rem;
        }
        
        .animate-in {
            opacity: 0;
            animation: fadeIn 0.5s forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .date-badge {
            background-color: #f0f4f8;
            color: #4a5568;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
        }
        
        .notification-btn {
            position: relative;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-dark">Dashboard Overview</h1>
            <div class="d-flex gap-3 align-items-center">
                <span class="date-badge">
                    <i class="bi bi-calendar me-2"></i><?php echo date('F j, Y'); ?>
                </span>
                <button class="btn btn-light notification-btn p-2 rounded-circle">
                    <i class="bi bi-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle d-flex align-items-center" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="me-2 d-none d-sm-block">
                            <div class="fw-semibold"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                            <small class="text-muted">Administrator</small>
                        </div>
                        <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../includes/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row mb-4 g-4">
            <div class="col-xl-2 col-md-4 col-sm-6 animate-in" style="animation-delay: 0.1s">
                <div class="stat-card users">
                    <i class="fas fa-users"></i>
                    <div class="stat-value"><?php echo $stats['users']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 animate-in" style="animation-delay: 0.2s">
                <div class="stat-card admins">
                    <i class="fas fa-user-shield"></i>
                    <div class="stat-value"><?php echo $stats['admins']; ?></div>
                    <div class="stat-label">Administrators</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 animate-in" style="animation-delay: 0.3s">
                <div class="stat-card unfinished">
                    <i class="fas fa-inbox"></i>
                    <div class="stat-value"><?php echo $stats['unfinished']; ?></div>
                    <div class="stat-label">Pending Complaints</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 animate-in" style="animation-delay: 0.4s">
                <div class="stat-card assigned">
                    <i class="fas fa-tasks"></i>
                    <div class="stat-value"><?php echo $stats['assigned']; ?></div>
                    <div class="stat-label">Active Cases</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 animate-in" style="animation-delay: 0.5s">
                <div class="stat-card resolved">
                    <i class="fas fa-check-circle"></i>
                    <div class="stat-value"><?php echo $stats['resolved']; ?></div>
                    <div class="stat-label">Resolved Cases</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 animate-in" style="animation-delay: 0.6s">
                <div class="stat-card" style="background: linear-gradient(135deg, #f72585, #b5179e);">
                    <i class="fas fa-chart-line"></i>
                    <div class="stat-value"><?php echo $stats['resolved'] + $stats['assigned']; ?></div>
                    <div class="stat-label">Total Cases</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Recent Complaints -->
            <div class="col-lg-8 animate-in" style="animation-delay: 0.7s">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Complaints</h5>
                        <a href="complaints.php" class="btn btn-sm btn-outline-primary">
                            View All <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
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
                                            <span class="badge rounded-pill <?php 
                                                echo 'badge-status-' . str_replace('_', '-', $complaint['status']); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $complaint['reviewer_name'] ? htmlspecialchars($complaint['reviewer_name']) : '<span class="text-muted">Not assigned</span>'; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($complaint['created_at'])); ?></td>
                                        <td>
                                            <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-outline-primary rounded-circle">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="col-lg-4 animate-in" style="animation-delay: 0.8s">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-3">
                            <a href="create.php?type=user" class="btn btn-primary quick-action-btn">
                                <i class="bi bi-person-plus"></i>
                                <h6 class="mb-0">Create User</h6>
                                <small>Add new system user</small>
                            </a>
                            <a href="complaints.php" class="btn btn-success quick-action-btn">
                                <i class="bi bi-list-task"></i>
                                <h6 class="mb-0">Manage Complaints</h6>
                                <small>View all cases</small>
                            </a>
                            <a href="settings.php" class="btn btn-secondary quick-action-btn">
                                <i class="bi bi-gear"></i>
                                <h6 class="mb-0">System Settings</h6>
                                <small>Configure application</small>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-feed">
                            <div class="d-flex mb-3">
                                <div class="avatar bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                    <i class="bi bi-person-check"></i>
                                </div>
                                <div>
                                    <small class="d-block text-muted">10 min ago</small>
                                    <p class="mb-0">New user registered - John Doe</p>
                                </div>
                            </div>
                            <div class="d-flex mb-3">
                                <div class="avatar bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div>
                                    <small class="d-block text-muted">1 hour ago</small>
                                    <p class="mb-0">Complaint #125 resolved</p>
                                </div>
                            </div>
                            <div class="d-flex mb-3">
                                <div class="avatar bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                    <i class="bi bi-exclamation-triangle"></i>
                                </div>
                                <div>
                                    <small class="d-block text-muted">3 hours ago</small>
                                    <p class="mb-0">System maintenance scheduled</p>
                                </div>
                            </div>
                            <div class="d-flex">
                                <div class="avatar bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                    <i class="bi bi-arrow-repeat"></i>
                                </div>
                                <div>
                                    <small class="d-block text-muted">Yesterday</small>
                                    <p class="mb-0">Database backup completed</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Simple animation trigger
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.animate-in');
            elements.forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
            });
        });

        // Real-time notification polling
        function updateNotifications() {
            fetch('notifications_api.php')
                .then(res => res.json())
                .then(data => {
                    if (data.notification_count !== undefined) {
                        // Update notification badge
                        const badge = document.querySelector('.notification-badge');
                        badge.textContent = data.notification_count;
                        badge.style.display = data.notification_count > 0 ? 'flex' : 'none';
                    }
                    if (data.activities) {
                        // Update recent activity feed
                        const feed = document.querySelector('.activity-feed');
                        if (feed) {
                            feed.innerHTML = '';
                            data.activities.forEach(act => {
                                let icon = 'bi-info-circle';
                                let color = 'primary';
                                let msg = '';
                                if (act.status === 'resolved') {
                                    icon = 'bi-check-circle'; color = 'success';
                                    msg = `Complaint #${act.id} resolved`;
                                } else if (act.status === 'assigned' || act.status === 'in_progress') {
                                    icon = 'bi-list-task'; color = 'info';
                                    msg = `Complaint #${act.id} assigned to ${act.reviewer_name || 'reviewer'}`;
                                } else {
                                    icon = 'bi-exclamation-triangle'; color = 'warning';
                                    msg = `New complaint: ${act.title}`;
                                }
                                feed.innerHTML += `
                                <div class="d-flex mb-3">
                                    <div class="avatar bg-${color} bg-opacity-10 text-${color} rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="bi ${icon}"></i>
                                    </div>
                                    <div>
                                        <small class="d-block text-muted">${new Date(act.created_at).toLocaleString([], {dateStyle:'medium', timeStyle:'short'})}</small>
                                        <p class="mb-0">${msg}</p>
                                    </div>
                                </div>`;
                            });
                        }
                    }
                });
        }
        // Poll every 10 seconds
        setInterval(updateNotifications, 10000);
        // Initial call
        updateNotifications();
    </script>
</body>
</html>
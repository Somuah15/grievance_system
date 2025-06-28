<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

require_role('reviewer');

// [Previous PHP code remains exactly the same until the HTML starts]
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resolved Complaints - ResolverIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .badge-high {
            background-color: #dc3545;
            color: white;
        }
        .badge-medium {
            background-color: #fd7e14;
            color: white;
        }
        .badge-low {
            background-color: #28a745;
            color: white;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: none;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
        }
        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
        }
        .table td {
            vertical-align: middle;
        }
        .action-btn {
            min-width: 80px;
        }
        .status-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            border-radius: 50rem;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-check-circle-fill text-success me-2"></i>Resolved Complaints</h2>
                <a href="checked.php" class="btn btn-primary">
                    <i class="bi bi-arrow-left-circle me-1"></i> View In Progress
                </a>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (empty($complaints)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-check-all text-muted" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">No Resolved Complaints</h5>
                        <p class="text-muted">You haven't resolved any complaints yet.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Resolved Complaints List</h5>
                        <small class="text-muted"><?php echo count($complaints); ?> resolved items</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="bi bi-hash me-1"></i>ID</th>
                                        <th><i class="bi bi-card-heading me-1"></i>Title</th>
                                        <th><i class="bi bi-exclamation-triangle me-1"></i>Priority</th>
                                        <th><i class="bi bi-calendar-check me-1"></i>Resolved At</th>
                                        <th><i class="bi bi-activity me-1"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($complaints as $complaint): ?>
                                    <tr>
                                        <td>#<?php echo $complaint['id']; ?></td>
                                        <td><?php echo htmlspecialchars($complaint['title']); ?></td>
                                        <td>
                                            <span class="status-badge <?php 
                                                echo $complaint['priority'] == 'high' ? 'badge-high' : 
                                                     ($complaint['priority'] == 'medium' ? 'badge-medium' : 'badge-low'); 
                                            ?>">
                                                <i class="bi <?php 
                                                    echo $complaint['priority'] == 'high' ? 'bi-exclamation-octagon-fill' : 
                                                         ($complaint['priority'] == 'medium' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill'); 
                                                ?> me-1"></i>
                                                <?php echo ucfirst($complaint['priority']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <i class="bi bi-clock-history me-1 text-muted"></i>
                                            <?php echo date('M d, Y H:i', strtotime($complaint['resolved_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-primary action-btn">
                                                    <i class="bi bi-eye me-1"></i>View
                                                </a>
                                                <?php 
                                                $resolved_time = strtotime($complaint['resolved_at']);
                                                $current_time = time();
                                                if (($current_time - $resolved_time) <= 21600): // 6 hours in seconds
                                                ?>
                                                    <a href="resolved.php?undo=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-warning action-btn" onclick="return confirm('Are you sure you want to undo resolution?')">
                                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Undo
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add nice hover effects
        document.querySelectorAll('.table tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(2px)';
                this.style.transition = 'transform 0.2s ease';
            });
            row.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });
    </script>
</body>
</html>
<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

require_role('reviewer');

// Get in-progress complaints assigned to this reviewer
$complaints = [];
$stmt = $conn->prepare("SELECT c.id, c.title, c.priority, c.created_at 
                        FROM complaints c 
                        WHERE c.assigned_to = ? AND (c.status = 'assigned' OR c.status = 'in_progress')
                        ORDER BY c.created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $complaints[] = $row;
}

// Handle marking as in progress
if (isset($_GET['start'])) {
    $complaint_id = intval($_GET['start']);
    
    $stmt = $conn->prepare("UPDATE complaints SET status = 'in_progress' WHERE id = ? AND assigned_to = ?");
    $stmt->bind_param("ii", $complaint_id, $_SESSION['user_id']);
    $stmt->execute();
    
    // Send notification to admin
    send_notification(1, "Reviewer {$_SESSION['name']} has started working on complaint #$complaint_id", "admin/complaints.php?action=view&id=$complaint_id");

    // Fetch complaint, user, and admin info for email
    $complaint_stmt = $conn->prepare("SELECT title, user_id FROM complaints WHERE id = ?");
    $complaint_stmt->bind_param("i", $complaint_id);
    $complaint_stmt->execute();
    $complaint_result = $complaint_stmt->get_result();
    if ($complaint = $complaint_result->fetch_assoc()) {
        // Get user email
        $user_stmt = $conn->prepare("SELECT email, name FROM users WHERE id = ?");
        $user_stmt->bind_param("i", $complaint['user_id']);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        if ($user = $user_result->fetch_assoc()) {
            $user_subject = "Your complaint is now being worked on";
            $user_body = "<p>Dear {$user['name']},</p><p>Your complaint '<strong>{$complaint['title']}</strong>' (ID: $complaint_id) is now being worked on by a reviewer.</p>";
            send_email_notification($user['email'], $user_subject, $user_body);
        }
        // Get admin email
        $admin_stmt = $conn->prepare("SELECT email, name FROM users WHERE role = 'admin' LIMIT 1");
        $admin_stmt->execute();
        $admin_result = $admin_stmt->get_result();
        if ($admin = $admin_result->fetch_assoc()) {
            $admin_subject = "A reviewer has started working on a complaint";
            $admin_body = "<p>Reviewer <strong>{$_SESSION['name']}</strong> has started working on complaint '<strong>{$complaint['title']}</strong>' (ID: $complaint_id).</p>";
            send_email_notification($admin['email'], $admin_subject, $admin_body);
        }
    }
    
    $_SESSION['message'] = "Complaint marked as in progress";
    header("Location: checked.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In Progress Complaints - ResolverIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --high-priority: #dc3545;
            --medium-priority: #fd7e14;
            --low-priority: #28a745;
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
        .priority-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            border-radius: 50rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .priority-high {
            background-color: var(--high-priority);
            color: white;
        }
        .priority-medium {
            background-color: var(--medium-priority);
            color: white;
        }
        .priority-low {
            background-color: var(--low-priority);
            color: white;
        }
        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
            white-space: nowrap;
        }
        .table td {
            vertical-align: middle;
        }
        .action-btn {
            min-width: 110px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .empty-state {
            text-align: center;
            padding: 3rem 0;
        }
        .empty-state-icon {
            font-size: 3rem;
            color: #adb5bd;
            margin-bottom: 1rem;
        }
        .progress-pulse {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--bs-primary);
            margin-right: 0.5rem;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.7; }
            50% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.7; }
        }
        .table-hover tbody tr {
            transition: all 0.2s ease;
        }
        .table-hover tbody tr:hover {
            transform: translateX(4px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <span class="progress-pulse"></span>
                    <i class="bi bi-hourglass-split text-primary me-2"></i>
                    In Progress Complaints
                </h2>
                <a href="inbox.php" class="btn btn-primary">
                    <i class="bi bi-inbox-fill me-1"></i> View New Complaints
                </a>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (empty($complaints)): ?>
                <div class="card">
                    <div class="card-body empty-state">
                        <i class="bi bi-hourglass-top empty-state-icon"></i>
                        <h4 class="mb-3">No Active Complaints</h4>
                        <p class="text-muted mb-4">You currently have no complaints assigned to you.</p>
                        <a href="inbox.php" class="btn btn-primary px-4">
                            <i class="bi bi-arrow-right-circle me-1"></i> Check for New Complaints
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-list-task me-2"></i>
                            Your Active Complaints
                        </h5>
                        <span class="badge bg-primary rounded-pill">
                            <?php echo count($complaints); ?> active
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4"><i class="bi bi-hash me-1"></i>ID</th>
                                        <th><i class="bi bi-card-heading me-1"></i>Title</th>
                                        <th><i class="bi bi-exclamation-triangle me-1"></i>Priority</th>
                                        <th><i class="bi bi-calendar me-1"></i>Date</th>
                                        <th class="pe-4"><i class="bi bi-activity me-1"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($complaints as $complaint): ?>
                                    <tr>
                                        <td class="ps-4">#<?php echo $complaint['id']; ?></td>
                                        <td>
                                            <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($complaint['title']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="priority-badge <?php 
                                                echo 'priority-' . $complaint['priority'];
                                            ?>">
                                                <i class="bi <?php 
                                                    echo $complaint['priority'] == 'high' ? 'bi-exclamation-octagon-fill' : 
                                                         ($complaint['priority'] == 'medium' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill'); 
                                                ?>"></i>
                                                <?php echo ucfirst($complaint['priority']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                <i class="bi bi-clock-history me-1"></i>
                                                <?php echo date('M d, Y', strtotime($complaint['created_at'])); ?>
                                            </span>
                                        </td>
                                        <td class="pe-4">
                                            <div class="d-flex gap-2">
                                                <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" 
                                                   class="btn btn-sm btn-primary action-btn"
                                                   data-bs-toggle="tooltip" 
                                                   data-bs-title="View details">
                                                    <i class="bi bi-eye-fill"></i> View
                                                </a>
                                                <a href="resolved.php?resolve=<?php echo $complaint['id']; ?>" 
                                                   class="btn btn-sm btn-success action-btn"
                                                   data-bs-toggle="tooltip" 
                                                   data-bs-title="Mark as resolved"
                                                   onclick="return confirm('Are you sure you want to mark this complaint as resolved?')">
                                                    <i class="bi bi-check-circle-fill"></i> Resolve
                                                </a>
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
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Add click effect to table rows
        document.querySelectorAll('.table tbody tr').forEach(row => {
            row.addEventListener('click', function(e) {
                // Don't trigger if clicking on an action button
                if (!e.target.closest('.action-btn')) {
                    const viewLink = this.querySelector('a[href*="view_complaint.php"]');
                    if (viewLink) {
                        window.location.href = viewLink.href;
                    }
                }
            });
            row.style.cursor = 'pointer';
        });
    </script>
</body>
</html>
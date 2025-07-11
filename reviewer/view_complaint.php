<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

require_role('reviewer');

$complaint_id = intval($_GET['id']);

// Verify the complaint is assigned to this reviewer
$stmt = $conn->prepare("SELECT c.*, u.name as user_name 
                        FROM complaints c
                        LEFT JOIN users u ON c.user_id = u.id
                        WHERE c.id = ? AND c.assigned_to = ?");
$stmt->bind_param("ii", $complaint_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    header("Location: inbox.php");
    exit();
}

$complaint = $result->fetch_assoc();

// Get messages
$messages = [];
$stmt = $conn->prepare("SELECT m.*, u.name as sender_name 
                        FROM messages m
                        LEFT JOIN users u ON m.sender_id = u.id
                        WHERE m.complaint_id = ?
                        ORDER BY m.created_at");
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

// Handle adding messages
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_message'])) {
    $message = sanitize_input($_POST['message']);
    $is_reviewer_note = isset($_POST['is_reviewer_note']) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO messages (complaint_id, sender_id, message, is_reviewer_note) 
                            VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisi", $complaint_id, $_SESSION['user_id'], $message, $is_reviewer_note);
    $stmt->execute();
    
    header("Location: view_complaint.php?id=$complaint_id");
    exit();
}

// Handle marking as resolved
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_resolved'])) {
    $stmt = $conn->prepare("UPDATE complaints SET status = 'resolved', resolved_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $complaint_id);
    $stmt->execute();
    
    // Send notification to admin
    send_notification(1, "Reviewer {$_SESSION['name']} has resolved complaint #$complaint_id", "admin/complaints.php?action=view&id=$complaint_id");
    
    // Send notification to user
    send_notification($complaint['user_id'], "Your complaint '{$complaint['title']}' has been resolved", "user/complaints.php");
    
    $_SESSION['message'] = "Complaint marked as resolved";
    header("Location: resolved.php");
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
            min-width: 100px;
        }
        .status-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            border-radius: 50rem;
        }
        .progress-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
            background-color: #0d6efd;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.7; }
            50% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.7; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-hourglass-split text-primary me-2"></i>In Progress Complaints</h2>
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
                    <div class="card-body text-center py-5">
                        <i class="bi bi-hourglass text-muted" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">No Complaints In Progress</h5>
                        <p class="text-muted">You currently have no complaints assigned to you.</p>
                        <a href="inbox.php" class="btn btn-primary mt-3">
                            <i class="bi bi-arrow-right-circle me-1"></i> Check for New Complaints
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list-task me-2"></i>Your Active Complaints</h5>
                        <small class="text-muted"><?php echo count($complaints); ?> in progress</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="bi bi-hash me-1"></i>ID</th>
                                        <th><i class="bi bi-card-heading me-1"></i>Title</th>
                                        <th><i class="bi bi-exclamation-triangle me-1"></i>Priority</th>
                                        <th><i class="bi bi-calendar me-1"></i>Date</th>
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
                                            <?php echo date('M d, Y', strtotime($complaint['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-primary action-btn">
                                                    <i class="bi bi-eye me-1"></i>View
                                                </a>
                                                <a href="resolved.php?resolve=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-success action-btn">
                                                    <i class="bi bi-check-circle me-1"></i>Resolve
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

        // Add confirmation for resolve action
        document.querySelectorAll('a[href*="resolve="]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to mark this complaint as resolved?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
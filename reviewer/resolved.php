<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

require_role('reviewer');

// Handle resolving complaints
if (isset($_GET['resolve'])) {
    $complaint_id = intval($_GET['resolve']);
    
    $stmt = $conn->prepare("UPDATE complaints SET status = 'resolved', resolved_at = NOW() WHERE id = ? AND assigned_to = ?");
    $stmt->bind_param("ii", $complaint_id, $_SESSION['user_id']);
    $stmt->execute();
    
    // Send notification to admin
    send_notification(1, "Reviewer {$_SESSION['name']} has resolved complaint #$complaint_id", "admin/complaints.php?action=view&id=$complaint_id");

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
            $user_subject = "Your complaint has been resolved";
            $user_body = "<p>Dear {$user['name']},</p><p>Your complaint '<strong>{$complaint['title']}</strong>' (ID: $complaint_id) has been marked as resolved by the reviewer.</p>";
            send_email_notification($user['email'], $user_subject, $user_body);
        }
        // Get admin email
        $admin_stmt = $conn->prepare("SELECT email, name FROM users WHERE role = 'admin' LIMIT 1");
        $admin_stmt->execute();
        $admin_result = $admin_stmt->get_result();
        if ($admin = $admin_result->fetch_assoc()) {
            $admin_subject = "A complaint has been resolved";
            $admin_body = "<p>Reviewer <strong>{$_SESSION['name']}</strong> has resolved complaint '<strong>{$complaint['title']}</strong>' (ID: $complaint_id).</p>";
            send_email_notification($admin['email'], $admin_subject, $admin_body);
        }
    }
    
    $_SESSION['message'] = "Complaint marked as resolved";
    header("Location: resolved.php");
    exit();
}

// Handle undoing resolution (within 6 hours)
if (isset($_GET['undo'])) {
    $complaint_id = intval($_GET['undo']);
    
    // Check if it's within 6 hours
    $stmt = $conn->prepare("SELECT resolved_at FROM complaints WHERE id = ? AND assigned_to = ?");
    $stmt->bind_param("ii", $complaint_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $complaint = $result->fetch_assoc();
        $resolved_time = strtotime($complaint['resolved_at']);
        $current_time = time();
        
        if (($current_time - $resolved_time) <= 21600) { // 6 hours in seconds
            $stmt = $conn->prepare("UPDATE complaints SET status = 'in_progress', resolved_at = NULL WHERE id = ?");
            $stmt->bind_param("i", $complaint_id);
            $stmt->execute();
            
            // Send notification to admin
            send_notification(1, "Reviewer {$_SESSION['name']} has undone resolution for complaint #$complaint_id", "admin/complaints.php?action=view&id=$complaint_id");
            
            $_SESSION['message'] = "Resolution undone successfully";
        } else {
            $_SESSION['error'] = "Cannot undo resolution after 6 hours";
        }
    }
    
    header("Location: resolved.php");
    exit();
}

// Get resolved complaints
$complaints = [];
$stmt = $conn->prepare("SELECT c.id, c.title, c.priority, c.resolved_at 
                        FROM complaints c 
                        WHERE c.assigned_to = ? AND c.status = 'resolved'
                        ORDER BY c.resolved_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $complaints[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resolved Complaints - ResolverIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Resolved Complaints</h2>
            <a href="checked.php" class="btn btn-primary">
                <i class="bi bi-check-circle"></i> View In Progress
            </a>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if (empty($complaints)): ?>
            <div class="alert alert-info">You haven't resolved any complaints yet.</div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Priority</th>
                                    <th>Resolved At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($complaints as $complaint): ?>
                                <tr>
                                    <td><?php echo $complaint['id']; ?></td>
                                    <td><?php echo htmlspecialchars($complaint['title']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $complaint['priority'] == 'high' ? 'badge-high' : 
                                                 ($complaint['priority'] == 'medium' ? 'badge-medium' : 'badge-low'); 
                                        ?>">
                                            <?php echo ucfirst($complaint['priority']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($complaint['resolved_at'])); ?></td>
                                    <td>
                                        <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                        <?php 
                                        $resolved_time = strtotime($complaint['resolved_at']);
                                        $current_time = time();
                                        if (($current_time - $resolved_time) <= 21600): // 6 hours in seconds
                                        ?>
                                            <a href="resolved.php?undo=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to undo resolution?')">Undo</a>
                                        <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
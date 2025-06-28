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
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>In Progress Complaints</h2>
            <a href="inbox.php" class="btn btn-primary">
                <i class="bi bi-inbox"></i> View New Complaints
            </a>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <?php if (empty($complaints)): ?>
            <div class="alert alert-info">You have no complaints in progress.</div>
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
                                    <th>Date</th>
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
                                    <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                                    <td>
                                        <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                        <a href="resolved.php?resolve=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-success">Mark Resolved</a>
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
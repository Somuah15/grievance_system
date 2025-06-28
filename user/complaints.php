<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

// Ensure only users can access
if ($_SESSION['role'] != 'user') {
    header("Location: ../index.php");
    exit();
}

// Handle new complaint submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_complaint'])) {
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $priority = sanitize_input($_POST['priority']);
    
    $stmt = $conn->prepare("INSERT INTO complaints (user_id, title, description, priority) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $_SESSION['user_id'], $title, $description, $priority);
    
    if ($stmt->execute()) {
        $complaint_id = $stmt->insert_id;
        // Send notification to admin
        send_notification(1, "New complaint submitted: $title", "admin/complaints.php?action=view&id=$complaint_id");

        // Optionally: update notification count for real-time dashboard (AJAX clients will poll notifications_api.php)
        // You can trigger a websocket or push event here if using such tech.

        // Send email to user
        require_once '../includes/mailer.php';
        $user_email = $_SESSION['email'];
        $user_name = $_SESSION['name'];
        $user_subject = "Your complaint has been received - ResolverIT";
        $user_body = "<p>Dear $user_name,</p><p>Your complaint titled '<strong>" . htmlspecialchars($title) . "</strong>' has been successfully submitted. Our team will review and address it as soon as possible.</p><p>Thank you for using ResolverIT.</p>";
        send_mail($user_email, $user_subject, $user_body);

        // Send email to admin
        $admin_email = getenv('ADMIN_EMAIL') ?: 'admin@yourdomain.com';
        $admin_subject = "New Complaint Submitted - ResolverIT";
        $admin_body = "<p>A new complaint has been submitted by <strong>$user_name</strong> (Email: $user_email).</p><p><strong>Title:</strong> " . htmlspecialchars($title) . "<br><strong>Priority:</strong> " . htmlspecialchars($priority) . "<br><strong>Description:</strong> " . nl2br(htmlspecialchars($description)) . "</p><p><a href='http://" . $_SERVER['HTTP_HOST'] . "/admin/complaints.php?action=view&id=$complaint_id'>View Complaint</a></p>";
        send_mail($admin_email, $admin_subject, $admin_body);

        // Optionally: set a flag or session variable if you want to trigger a frontend update
        $_SESSION['notification_updated'] = true;

        $_SESSION['message'] = "Complaint submitted successfully";
        header("Location: complaints.php");
        exit();
    } else {
        $error = "Error submitting complaint: " . $conn->error;
    }
}

// Get all complaints by this user
$complaints = [];
$result = $conn->query("SELECT id, title, priority, status, created_at 
                        FROM complaints 
                        WHERE user_id = {$_SESSION['user_id']} 
                        ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $complaints[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints - ResolverIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Complaints</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newComplaintModal">
                <i class="bi bi-plus-circle"></i> New Complaint
            </button>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (empty($complaints)): ?>
            <div class="alert alert-info">You haven't submitted any complaints yet.</div>
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
                                    <th>Status</th>
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
                                        <span class="badge <?php echo 'badge-status-' . str_replace('_', '-', $complaint['status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                    </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                                    <td>
                                        <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-primary">View</a>
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

    <!-- New Complaint Modal -->
    <div class="modal fade" id="newComplaintModal" tabindex="-1" aria-labelledby="newComplaintModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newComplaintModalLabel">Submit New Complaint</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="submit_complaint" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
include 'includes/config.php';
include 'includes/auth.php';

$complaint_id = intval($_GET['id']);

// Verify access
if (!can_access_complaint($complaint_id)) {
    header("Location: unauthorized.php");
    exit();
}

// Get complaint details
$stmt = $conn->prepare("SELECT c.*, u.name as reviewer_name 
                        FROM complaints c
                        LEFT JOIN users u ON c.assigned_to = u.id
                        WHERE c.id = ?");
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    header("Location: dashboard.php");
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

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        $new_status = sanitize_input($_POST['status']);
        $stmt = $conn->prepare("UPDATE complaints SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $complaint_id);
        $stmt->execute();
        
        // Send notification
        $message = "Status of your complaint #$complaint_id changed to " . ucfirst($new_status);
        send_notification($complaint['user_id'], $message);
        
        header("Location: view_complaint.php?id=$complaint_id");
        exit();
    }
    
    if (isset($_POST['add_message'])) {
        $message = sanitize_input($_POST['message']);
        $is_reviewer_note = isset($_POST['is_reviewer_note']) ? 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO messages (complaint_id, sender_id, message, is_reviewer_note) 
                                VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisi", $complaint_id, $_SESSION['user_id'], $message, $is_reviewer_note);
        $stmt->execute();
        
        header("Location: view_complaint.php?id=$complaint_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Complaint - ResolverIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Complaint #<?php echo $complaint['id']; ?></h2>
        
        <div class="card mb-4">
            <div class="card-header">
                Complaint Details
            </div>
            <div class="card-body">
                <h5><?php echo htmlspecialchars($complaint['title']); ?></h5>
                <p><?php echo nl2br(htmlspecialchars($complaint['description'])); ?></p>
                
                <div class="row">
                    <div class="col-md-4">
                        <strong>Status:</strong> 
                        <span class="badge bg-<?php 
                            echo $complaint['status'] == 'resolved' ? 'success' : 
                                 ($complaint['status'] == 'in_progress' ? 'warning' : 'primary'); 
                        ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                        </span>
                    </div>
                    <div class="col-md-4">
                        <strong>Priority:</strong> 
                        <span class="badge bg-<?php 
                            echo $complaint['priority'] == 'high' ? 'danger' : 
                                 ($complaint['priority'] == 'medium' ? 'warning' : 'success'); 
                        ?>">
                            <?php echo ucfirst($complaint['priority']); ?>
                        </span>
                    </div>
                    <div class="col-md-4">
                        <strong>Assigned To:</strong> 
                        <?php echo $complaint['reviewer_name'] ? htmlspecialchars($complaint['reviewer_name']) : 'Not assigned'; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status Update Form (for admin/reviewer) -->
        <?php if ($_SESSION['role'] != 'user'): ?>
        <div class="card mb-4">
            <div class="card-header">
                Update Status
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <select name="status" class="form-select">
                            <option value="new" <?php echo $complaint['status'] == 'new' ? 'selected' : ''; ?>>New</option>
                            <option value="in_progress" <?php echo $complaint['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $complaint['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                    </div>
                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Messages Section -->
        <div class="card mb-4">
            <div class="card-header">
                Messages
            </div>
            <div class="card-body">
                <?php foreach ($messages as $message): ?>
                <div class="mb-3 border-bottom pb-3">
                    <strong><?php echo $message['sender_name'] ? htmlspecialchars($message['sender_name']) : 'System'; ?></strong>
                    <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></small>
                    <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                </div>
                <?php endforeach; ?>
                
                <!-- Add Message Form -->
                <form method="POST">
                    <div class="mb-3">
                        <textarea name="message" class="form-control" rows="3" required></textarea>
                    </div>
                    <?php if ($_SESSION['role'] == 'reviewer'): ?>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="isReviewerNote" name="is_reviewer_note">
                        <label class="form-check-label" for="isReviewerNote">Internal Note (visible to reviewers/admins only)</label>
                    </div>
                    <?php endif; ?>
                    <button type="submit" name="add_message" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
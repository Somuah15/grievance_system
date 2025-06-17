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
    <title>View Complaint - ResolverIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/reviewer.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .badge-high {
            background-color: var(--accent-color);
        }
        
        .badge-medium {
            background-color: #f39c12;
        }
        
        .badge-low {
            background-color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Complaint #<?php echo $complaint['id']; ?></h2>
            <div>
                <a href="<?php echo $complaint['status'] == 'resolved' ? 'resolved.php' : 'checked.php'; ?>" class="btn btn-secondary">Back</a>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Complaint Details</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Title</h6>
                        <p><?php echo htmlspecialchars($complaint['title']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <h6>Status</h6>
                        <span class="badge <?php 
                            echo $complaint['status'] == 'resolved' ? 'bg-success' : 
                                 ($complaint['status'] == 'in_progress' ? 'bg-warning' : 'bg-primary'); 
                        ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                        </span>
                    </div>
                    <div class="col-md-3">
                        <h6>Priority</h6>
                        <span class="badge <?php 
                            echo $complaint['priority'] == 'high' ? 'badge-high' : 
                                 ($complaint['priority'] == 'medium' ? 'badge-medium' : 'badge-low'); 
                        ?>">
                            <?php echo ucfirst($complaint['priority']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h6>Description</h6>
                    <div class="border p-3 rounded bg-light">
                        <?php echo nl2br(htmlspecialchars($complaint['description'])); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Messages Section -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Messages</h5>
                    <?php if ($complaint['status'] != 'resolved'): ?>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                            <i class="bi bi-plus"></i> Add Message
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($messages)): ?>
                    <div class="alert alert-info">No messages yet.</div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($messages as $message): ?>
                            <div class="mb-3 border-bottom pb-3">
                                <div class="d-flex justify-content-between">
                                    <strong><?php echo $message['sender_name'] ? htmlspecialchars($message['sender_name']) : 'System'; ?></strong>
                                    <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></small>
                                </div>
                                <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($complaint['status'] != 'resolved'): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Resolution</h5>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return confirm('Are you sure you want to mark this complaint as resolved?')">
                        <button type="submit" name="mark_resolved" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Mark as Resolved
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- New Message Modal -->
    <div class="modal fade" id="newMessageModal" tabindex="-1" aria-labelledby="newMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newMessageModalLabel">Add Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" name="message" rows="5" required></textarea>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="isReviewerNote" name="is_reviewer_note">
                            <label class="form-check-label" for="isReviewerNote">Internal Note (visible only to reviewers/admins)</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_message" class="btn btn-primary">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

require_role('admin');

$complaint_id = intval($_GET['id']);

// Add these headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Get complaint details
$stmt = $conn->prepare("SELECT c.*, u.name as reviewer_name 
                        FROM complaints c
                        LEFT JOIN users u ON c.assigned_to = u.id
                        WHERE c.id = ?");
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    header("Location: complaints.php");
    exit();
}

$complaint = $result->fetch_assoc();

// Get messages
$messages = [];
$stmt = $conn->prepare("SELECT m.*, u.name as sender_name, u.role as sender_role 
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

// Handle assigning reviewer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_reviewer'])) {
    $reviewer_id = intval($_POST['reviewer_id']);
    $priority = sanitize_input($_POST['priority']);
    
    $stmt = $conn->prepare("UPDATE complaints SET assigned_to = ?, priority = ?, status = 'assigned' WHERE id = ?");
    $stmt->bind_param("isi", $reviewer_id, $priority, $complaint_id);
    $stmt->execute();
    
    // Send notification to reviewer
    send_notification($reviewer_id, "You've been assigned a new complaint: {$complaint['title']}", "reviewer/inbox.php");
    
    $_SESSION['message'] = "Complaint assigned successfully";
    header("Location: view_complaint.php?id=$complaint_id");
    exit();
}

// Handle marking as resolved
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_resolved'])) {
    $stmt = $conn->prepare("UPDATE complaints SET status = 'resolved', resolved_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $complaint_id);
    $stmt->execute();
    
    // Send notification to user
    send_notification($complaint['user_id'], "Your complaint '{$complaint['title']}' has been resolved", "user/complaints.php");
    
    $_SESSION['message'] = "Complaint marked as resolved";
    header("Location: view_complaint.php?id=$complaint_id");
    exit();
}

// Get all reviewers for assignment
$reviewers = [];
$result = $conn->query("SELECT id, name FROM users WHERE role = 'reviewer' AND is_active = 1");
while ($row = $result->fetch_assoc()) {
    $reviewers[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Details - ResolverIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Case #<?php echo $complaint['id']; ?></h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="complaints.php">Cases</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Details</li>
                    </ol>
                </nav>
            </div>
            <a href="complaints.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Cases
            </a>
        </div>
        
        <!-- Case Details Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Case Details</h5>
                <div>
                    <span class="badge <?php echo 'badge-status-' . str_replace('_', '-', $complaint['status']); ?> me-2">
                        <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                    </span>
                    <span class="badge <?php echo 'badge-priority-' . $complaint['priority']; ?>">
                        <?php echo ucfirst($complaint['priority']); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h4 class="mb-3"><?php echo htmlspecialchars($complaint['title']); ?></h4>
                        <div class="bg-light p-3 rounded">
                            <?php echo nl2br(htmlspecialchars($complaint['description'])); ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title text-muted mb-3">Case Information</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                        <span class="text-muted">Submitted:</span>
                                        <span><?php echo date('M j, Y g:i A', strtotime($complaint['created_at'])); ?></span>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                        <span class="text-muted">Submitted By:</span>
                                        <span>Anonymous User</span>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                        <span class="text-muted">Assigned To:</span>
                                        <span>
                                            <?php if ($complaint['reviewer_name']): ?>
                                                <span class="badge bg-primary-light text-primary">
                                                    <?php echo htmlspecialchars($complaint['reviewer_name']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Not assigned</span>
                                            <?php endif; ?>
                                        </span>
                                    </li>
                                    <?php if ($complaint['status'] == 'resolved'): ?>
                                        <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                            <span class="text-muted">Resolved On:</span>
                                            <span><?php echo date('M j, Y', strtotime($complaint['resolved_at'])); ?></span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Messages Section -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Case Communications</h5>
                <?php if ($complaint['status'] != 'resolved'): ?>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                        <i class="fas fa-plus me-2"></i> Add Message
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($messages)): ?>
                    <div class="alert alert-info">No messages yet.</div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($messages as $message): ?>
                            <div class="timeline-item">
                                <div class="timeline-header">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-3">
                                            <div class="avatar-title bg-primary-light text-primary rounded-circle">
                                                <?php echo strtoupper(substr($message['sender_name'], 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="timeline-sender mb-0"><?php echo htmlspecialchars($message['sender_name']); ?></h6>
                                            <small class="text-muted"><?php echo ucfirst($message['sender_role']); ?></small>
                                        </div>
                                    </div>
                                    <small class="timeline-time"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></small>
                                </div>
                                <div class="timeline-content mt-2">
                                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($complaint['status'] != 'resolved'): ?>
            <!-- Assignment Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Case Assignment</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Assign to Reviewer</label>
                                <select name="reviewer_id" class="form-select" required>
                                    <option value="">Select Reviewer</option>
                                    <?php foreach ($reviewers as $reviewer): ?>
                                        <option value="<?php echo $reviewer['id']; ?>" <?php echo $reviewer['id'] == $complaint['assigned_to'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($reviewer['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Priority Level</label>
                                <select name="priority" class="form-select" required>
                                    <option value="low" <?php echo $complaint['priority'] == 'low' ? 'selected' : ''; ?>>Low Priority</option>
                                    <option value="medium" <?php echo $complaint['priority'] == 'medium' ? 'selected' : ''; ?>>Medium Priority</option>
                                    <option value="high" <?php echo $complaint['priority'] == 'high' ? 'selected' : ''; ?>>High Priority</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="assign_reviewer" class="btn btn-primary">
                                    <?php echo $complaint['assigned_to'] ? 'Update Assignment' : 'Assign Reviewer'; ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Resolution Section -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Case Resolution</h5>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return confirm('Are you sure you want to mark this case as resolved?')">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i> 
                            Marking this case as resolved will notify the submitter and close the case.
                        </div>
                        <button type="submit" name="mark_resolved" class="btn btn-success">
                            <i class="fas fa-check-circle me-2"></i> Mark as Resolved
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- New Message Modal -->
    <div class="modal fade" id="newMessageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Case Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="add_message.php">
                    <div class="modal-body">
                        <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Your Message</label>
                            <textarea class="form-control" name="message" rows="5" required placeholder="Type your message here..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

require_role('user');

$complaint_id = intval($_GET['id']);

// Verify the complaint belongs to this user
$stmt = $conn->prepare("SELECT c.*, u.name as reviewer_name 
                        FROM complaints c
                        LEFT JOIN users u ON c.assigned_to = u.id
                        WHERE c.id = ? AND c.user_id = ?");
$stmt->bind_param("ii", $complaint_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    header("Location: complaints.php");
    exit();
}

$complaint = $result->fetch_assoc();

// Get messages (excluding reviewer notes)
$messages = [];
$stmt = $conn->prepare("SELECT m.*, u.name as sender_name 
                        FROM messages m
                        LEFT JOIN users u ON m.sender_id = u.id
                        WHERE m.complaint_id = ? AND m.is_reviewer_note = 0
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
    
    $stmt = $conn->prepare("INSERT INTO messages (complaint_id, sender_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $complaint_id, $_SESSION['user_id'], $message);
    $stmt->execute();
    
    // Send notification to admin
    send_notification(1, "User {$_SESSION['name']} added a message to complaint #$complaint_id", "admin/complaints.php?action=view&id=$complaint_id");
    
    header("Location: view_complaint.php?id=$complaint_id");
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
    <link href="../../assets/css/user.css" rel="stylesheet">
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
        
        .badge-new {
            background-color: var(--accent-color);
        }
        
        .badge-assigned {
            background-color: var(--primary-color);
        }
        
        .badge-in_progress {
            background-color: #f39c12;
        }
        
        .badge-resolved {
            background-color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Complaint #<?php echo $complaint['id']; ?></h2>
            <a href="complaints.php" class="btn btn-secondary">Back to My Complaints</a>
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
                            echo $complaint['status'] == 'new' ? 'badge-new' : 
                                 ($complaint['status'] == 'assigned' ? 'badge-assigned' : 
                                  ($complaint['status'] == 'in_progress' ? 'badge-in_progress' : 'badge-resolved')); 
                        ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                        </span>
                    </div>
                    <div class="col-md-3">
                        <h6>Priority</h6>
                        <span class="badge <?php 
                            echo $complaint['priority'] == 'high' ? 'bg-danger' : 
                                 ($complaint['priority'] == 'medium' ? 'bg-warning text-dark' : 'bg-success'); 
                        ?>">
                            <?php echo ucfirst($complaint['priority']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Assigned To</h6>
                        <p><?php echo $complaint['reviewer_name'] ? htmlspecialchars($complaint['reviewer_name']) : 'Not assigned yet'; ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Date Submitted</h6>
                        <p><?php echo date('M d, Y H:i', strtotime($complaint['created_at'])); ?></p>
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
                <h5 class="mb-0">Messages</h5>
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
                
                <?php if ($complaint['status'] != 'resolved'): ?>
                <form method="POST" class="mt-4">
                    <div class="mb-3">
                        <label class="form-label">Add Message</label>
                        <textarea class="form-control" name="message" rows="3" required></textarea>
                    </div>
                    <button type="submit" name="add_message" class="btn btn-primary">Send Message</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
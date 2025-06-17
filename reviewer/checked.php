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
            transition: all 0.3s;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            margin-bottom: 0.2rem;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }
        
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            color: white;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card.users {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        
        .stat-card.admins {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }
        
        .stat-card.unfinished {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        
        .stat-card.assigned {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        
        .stat-card.resolved {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }
        
        .stat-card .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .stat-card .stat-label {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .recent-complaints .badge {
            padding: 0.5em 0.75em;
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
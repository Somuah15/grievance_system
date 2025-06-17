<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

// Ensure only admins can access
if ($_SESSION['role'] != 'reviewer') {
    header("Location: ../login.php");
    exit();
}

// Get filter parameters
$filter = isset($_GET['filter']) ? sanitize_input($_GET['filter']) : 'all';
$priority = isset($_GET['priority']) ? sanitize_input($_GET['priority']) : 'all';

// Base query
$query = "SELECT c.id, c.title, c.priority, c.status, c.created_at, c.assigned_to, u.name as reviewer_name 
          FROM complaints c 
          LEFT JOIN users u ON c.assigned_to = u.id 
          WHERE 1=1";

// Apply filters
if ($filter != 'all') {
    $query .= " AND c.status = '$filter'";
}

if ($priority != 'all') {
    $query .= " AND c.priority = '$priority'";
}

$query .= " ORDER BY c.created_at DESC";

// Get complaints
$complaints = [];
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $complaints[] = $row;
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaints - ResolverIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Same styles as dashboard.php */
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
        <h2 class="mb-4">Complaint Management</h2>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="filter" class="form-select">
                            <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="new" <?php echo $filter == 'new' ? 'selected' : ''; ?>>New</option>
                            <option value="assigned" <?php echo $filter == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                            <option value="in_progress" <?php echo $filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $filter == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="all" <?php echo $priority == 'all' ? 'selected' : ''; ?>>All Priorities</option>
                            <option value="low" <?php echo $priority == 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo $priority == 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo $priority == 'high' ? 'selected' : ''; ?>>High</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </form>
            </div>
        </div>
        
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
                                <th>Assigned To</th>
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
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo $complaint['reviewer_name'] ? htmlspecialchars($complaint['reviewer_name']) : 'Not assigned'; ?></td>
                                <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="view_complaint.php?id=<?php echo $complaint['id']; ?>">View Details</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                           
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

// Ensure only admins can access
if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Add these headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

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

// Get reviewers for assignment
$reviewers = [];
$result = $conn->query("SELECT id, name FROM users WHERE role = 'reviewer' AND is_active = 1");
while ($row = $result->fetch_assoc()) {
    $reviewers[] = $row;
}

// Handle export request
if (isset($_GET['export'])) {
    $format = sanitize_input($_GET['format']);
    $start_date = sanitize_input($_GET['start_date']);
    $end_date = sanitize_input($_GET['end_date']);
    
    // Modify query based on filters
    $export_query = "SELECT c.id, c.title, c.priority, c.status, c.created_at, u.name as reviewer_name 
                    FROM complaints c 
                    LEFT JOIN users u ON c.assigned_to = u.id 
                    WHERE c.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'";
    
    if ($filter != 'all') {
        $export_query .= " AND c.status = '$filter'";
    }
    
    if ($priority != 'all') {
        $export_query .= " AND c.priority = '$priority'";
    }
    
    $export_query .= " ORDER BY c.created_at DESC";
    
    $result = $conn->query($export_query);
    $export_data = [];
    while ($row = $result->fetch_assoc()) {
        $export_data[] = $row;
    }
    
    // Generate CSV
    if ($format == 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="complaints_export_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Title', 'Priority', 'Status', 'Created At', 'Assigned To'));
        
        foreach ($export_data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit();
    }
    // Generate Excel (simplified example)
    elseif ($format == 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="complaints_export_' . date('Y-m-d') . '.xls"');
        
        echo '<table border="1">';
        echo '<tr><th>ID</th><th>Title</th><th>Priority</th><th>Status</th><th>Created At</th><th>Assigned To</th></tr>';
        
        foreach ($export_data as $row) {
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . htmlspecialchars($row['title']) . '</td>';
            echo '<td>' . $row['priority'] . '</td>';
            echo '<td>' . $row['status'] . '</td>';
            echo '<td>' . $row['created_at'] . '</td>';
            echo '<td>' . htmlspecialchars($row['reviewer_name']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        exit();
    }
}


?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Management - ResolverIT</title>
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
                <h1 class="h3 mb-0">Case Management</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Cases</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="fas fa-file-export me-2"></i> Export
                </button>
            </div>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Filter Card -->
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
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter me-2"></i> Apply Filters
                        </button>
                        <a href="complaints.php" class="btn btn-outline-secondary">
                            <i class="fas fa-sync-alt"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Cases Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Case ID</th>
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
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded me-3">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <strong>#<?php echo $complaint['id']; ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($complaint['title']); ?></td>
                                <td>
                                    <span class="badge <?php echo 'badge-priority-' . $complaint['priority']; ?>">
                                        <?php echo ucfirst($complaint['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo 'badge-status-' . str_replace('_', '-', $complaint['status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($complaint['reviewer_name']): ?>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-2">
                                                <div class="avatar-title bg-primary-light text-primary rounded-circle">
                                                    <?php echo strtoupper(substr($complaint['reviewer_name'], 0, 1)); ?>
                                                </div>
                                            </div>
                                            <?php echo htmlspecialchars($complaint['reviewer_name']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($complaint['created_at'])); ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="view_complaint.php?id=<?php echo $complaint['id']; ?>">
                                                    <i class="fas fa-eye me-2"></i> View Details
                                                </a>
                                            </li>
                                            <?php if ($complaint['status'] != 'resolved'): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><h6 class="dropdown-header">Assign To</h6></li>
                                                <?php foreach ($reviewers as $reviewer): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="assign_complaint.php?complaint_id=<?php echo $complaint['id']; ?>&reviewer_id=<?php echo $reviewer['id']; ?>">
                                                            <i class="fas fa-user-check me-2"></i>
                                                            <?php echo htmlspecialchars($reviewer['name']); ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                                <?php if ($complaint['assigned_to']): ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="unassign_complaint.php?id=<?php echo $complaint['id']; ?>">
                                                            <i class="fas fa-user-times me-2"></i> Unassign
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- In the export modal (replace existing modal) -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Cases</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="GET" action="complaints.php">
                <input type="hidden" name="export" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Format</label>
                        <select name="format" class="form-select" required>
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text">From</span>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text">To</span>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Filters</label>
                        <div class="form-control bg-light">
                            Status: <?php echo $filter == 'all' ? 'All' : ucfirst($filter); ?>,
                            Priority: <?php echo $priority == 'all' ? 'All' : ucfirst($priority); ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Export</button>
                </div>
            </form>
        </div>
    </div>
</div>


    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Cases</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Format</label>
                        <select class="form-select">
                            <option>CSV</option>
                            <option>Excel</option>
                            <option>PDF</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <div class="input-group">
                            <input type="date" class="form-control">
                            <span class="input-group-text">to</span>
                            <input type="date" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Export</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
</body>
</html>
<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

// Add these headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Handle export requests
if (isset($_GET['export'])) {
    // First get all the data needed for exports
    $stats = [];
    $result = $conn->query("SELECT 
        COUNT(*) as total_complaints,
        SUM(status = 'resolved') as resolved,
        SUM(status = 'new') as new,
        SUM(status = 'assigned') as assigned,
        SUM(status = 'in_progress') as in_progress,
        SUM(priority = 'high') as high_count,
        SUM(priority = 'medium') as medium_count,
        SUM(priority = 'low') as low_count
        FROM complaints");
    $stats = $result->fetch_assoc();

    $monthly_data = [];
    $result = $conn->query("SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count,
        SUM(status = 'resolved') as resolved
        FROM complaints
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month");
    while ($row = $result->fetch_assoc()) {
        $monthly_data[] = $row;
    }

    $reviewers = [];
    $result = $conn->query("SELECT 
        u.id, u.name,
        COUNT(c.id) as total_assigned,
        SUM(c.status = 'resolved') as resolved
        FROM users u
        LEFT JOIN complaints c ON u.id = c.assigned_to
        WHERE u.role = 'reviewer' AND u.is_active = 1
        GROUP BY u.id");
    while ($row = $result->fetch_assoc()) {
        $reviewers[] = $row;
    }

    $export_type = $_GET['export'];
    
    if ($export_type == 'pdf') {
        // Generate PDF using Dompdf
        require_once '../vendor/autoload.php';
        $dompdf = new Dompdf\Dompdf();
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { color: #2c3e50; border-bottom: 2px solid #4361ee; padding-bottom: 10px; }
                h2 { color: #4361ee; margin-top: 25px; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th { background-color: #4361ee; color: white; padding: 10px; text-align: left; }
                td { padding: 8px; border: 1px solid #ddd; }
                .summary-card { 
                    border: 1px solid #ddd; 
                    padding: 15px; 
                    margin: 10px 0; 
                    border-radius: 5px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                }
                .text-primary { color: #4361ee; }
                .text-success { color: #4cc9f0; }
                .text-danger { color: #f72585; }
                .text-warning { color: #f8961e; }
                .progress { height: 20px; background-color: #e9ecef; border-radius: 4px; overflow: hidden; }
                .progress-bar { background-color: #4cc9f0; height: 100%; text-align: center; color: white; font-size: 12px; line-height: 20px; }
            </style>
        </head>
        <body>
            <h1>System Reports - <?php echo date('Y-m-d'); ?></h1>
            
            <!-- Summary Section -->
            <h2>Summary Statistics</h2>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                <div class="summary-card">
                    <h3>Total Cases</h3>
                    <p style="font-size: 24px; font-weight: bold;" class="text-primary"><?php echo $stats['total_complaints']; ?></p>
                </div>
                <div class="summary-card">
                    <h3>Resolved</h3>
                    <p style="font-size: 24px; font-weight: bold;" class="text-success"><?php echo $stats['resolved']; ?></p>
                    <small>Resolution Rate: <?php echo $stats['total_complaints'] > 0 ? round(($stats['resolved']/$stats['total_complaints'])*100) : 0; ?>%</small>
                </div>
                <div class="summary-card">
                    <h3>High Priority</h3>
                    <p style="font-size: 24px; font-weight: bold;" class="text-danger"><?php echo $stats['high_count']; ?></p>
                    <small><?php echo $stats['total_complaints'] > 0 ? round(($stats['high_count']/$stats['total_complaints'])*100) : 0; ?>% of total</small>
                </div>
                <div class="summary-card">
                    <h3>Pending</h3>
                    <p style="font-size: 24px; font-weight: bold;" class="text-warning"><?php echo $stats['new'] + $stats['assigned'] + $stats['in_progress']; ?></p>
                    <small>Active cases</small>
                </div>
            </div>
            
            <!-- Case Status Breakdown -->
            <h2>Case Status Breakdown</h2>
            <table>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
                <tr>
                    <td>Resolved</td>
                    <td><?php echo $stats['resolved']; ?></td>
                    <td><?php echo $stats['total_complaints'] > 0 ? round(($stats['resolved']/$stats['total_complaints'])*100) : 0; ?>%</td>
                </tr>
                <tr>
                    <td>New</td>
                    <td><?php echo $stats['new']; ?></td>
                    <td><?php echo $stats['total_complaints'] > 0 ? round(($stats['new']/$stats['total_complaints'])*100) : 0; ?>%</td>
                </tr>
                <tr>
                    <td>Assigned</td>
                    <td><?php echo $stats['assigned']; ?></td>
                    <td><?php echo $stats['total_complaints'] > 0 ? round(($stats['assigned']/$stats['total_complaints'])*100) : 0; ?>%</td>
                </tr>
                <tr>
                    <td>In Progress</td>
                    <td><?php echo $stats['in_progress']; ?></td>
                    <td><?php echo $stats['total_complaints'] > 0 ? round(($stats['in_progress']/$stats['total_complaints'])*100) : 0; ?>%</td>
                </tr>
            </table>
            
            <!-- Monthly Trends -->
            <h2>Monthly Trends (Last 6 Months)</h2>
            <table>
                <tr>
                    <th>Month</th>
                    <th>New Cases</th>
                    <th>Resolved Cases</th>
                </tr>
                <?php foreach ($monthly_data as $month): ?>
                <tr>
                    <td><?php echo date('M Y', strtotime($month['month'].'-01')); ?></td>
                    <td><?php echo $month['count']; ?></td>
                    <td><?php echo $month['resolved']; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            
            <!-- Reviewer Performance -->
            <h2>Reviewer Performance</h2>
            <table>
                <tr>
                    <th>Reviewer</th>
                    <th>Assigned</th>
                    <th>Resolved</th>
                    <th>Resolution Rate</th>
                </tr>
                <?php foreach ($reviewers as $reviewer): ?>
                <tr>
                    <td><?php echo htmlspecialchars($reviewer['name']); ?></td>
                    <td><?php echo $reviewer['total_assigned']; ?></td>
                    <td><?php echo $reviewer['resolved']; ?></td>
                    <td>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?php echo $reviewer['total_assigned'] > 0 ? ($reviewer['resolved']/$reviewer['total_assigned'])*100 : 0; ?>%">
                                <?php echo $reviewer['total_assigned'] > 0 ? round(($reviewer['resolved']/$reviewer['total_assigned'])*100) : 0; ?>%
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            
            <p style="margin-top: 30px; font-size: 11px; color: #777;">
                Generated on <?php echo date('Y-m-d H:i:s'); ?> by <?php echo $_SESSION['name']; ?>
            </p>
        </body>
        </html>
        <?php
        $html = ob_get_clean();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("reports_export_".date('Y-m-d').".pdf", ["Attachment" => true]);
        exit();
    }
    elseif ($export_type == 'excel') {
        // Excel Export
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="reports_export_'.date('Y-m-d').'.xls"');
        
        // Start Excel output
        echo "<html>";
        echo "<head>";
        echo "<style>";
        echo "td { padding: 5px; border: 1px solid #ddd; }";
        echo "th { background-color: #4361ee; color: white; padding: 8px; border: 1px solid #ddd; }";
        echo "</style>";
        echo "</head>";
        echo "<body>";
        
        // Summary Section
        echo "<h2>System Reports - ".date('Y-m-d')."</h2>";
        echo "<h3>Summary Statistics</h3>";
        echo "<table border='1'>";
        echo "<tr>";
        echo "<th>Metric</th>";
        echo "<th>Value</th>";
        echo "</tr>";
        echo "<tr><td>Total Cases</td><td>".$stats['total_complaints']."</td></tr>";
        echo "<tr><td>Resolved</td><td>".$stats['resolved']." (".($stats['total_complaints'] > 0 ? round(($stats['resolved']/$stats['total_complaints'])*100) : 0)."%)</td></tr>";
        echo "<tr><td>High Priority</td><td>".$stats['high_count']." (".($stats['total_complaints'] > 0 ? round(($stats['high_count']/$stats['total_complaints'])*100) : 0)."%)</td></tr>";
        echo "<tr><td>Pending Cases</td><td>".($stats['new'] + $stats['assigned'] + $stats['in_progress'])."</td></tr>";
        echo "</table>";
        
        // Case Status Breakdown
        echo "<h3>Case Status Breakdown</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Status</th><th>Count</th><th>Percentage</th></tr>";
        echo "<tr><td>Resolved</td><td>".$stats['resolved']."</td><td>".($stats['total_complaints'] > 0 ? round(($stats['resolved']/$stats['total_complaints'])*100) : 0)."%</td></tr>";
        echo "<tr><td>New</td><td>".$stats['new']."</td><td>".($stats['total_complaints'] > 0 ? round(($stats['new']/$stats['total_complaints'])*100) : 0)."%</td></tr>";
        echo "<tr><td>Assigned</td><td>".$stats['assigned']."</td><td>".($stats['total_complaints'] > 0 ? round(($stats['assigned']/$stats['total_complaints'])*100) : 0)."%</td></tr>";
        echo "<tr><td>In Progress</td><td>".$stats['in_progress']."</td><td>".($stats['total_complaints'] > 0 ? round(($stats['in_progress']/$stats['total_complaints'])*100) : 0)."%</td></tr>";
        echo "</table>";
        
        // Monthly Trends
        echo "<h3>Monthly Trends (Last 6 Months)</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Month</th><th>New Cases</th><th>Resolved Cases</th></tr>";
        foreach ($monthly_data as $month) {
            echo "<tr>";
            echo "<td>".date('M Y', strtotime($month['month'].'-01'))."</td>";
            echo "<td>".$month['count']."</td>";
            echo "<td>".$month['resolved']."</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Reviewer Performance
        echo "<h3>Reviewer Performance</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Reviewer</th><th>Assigned</th><th>Resolved</th><th>Resolution Rate</th></tr>";
        foreach ($reviewers as $reviewer) {
            $resolution_rate = $reviewer['total_assigned'] > 0 ? round(($reviewer['resolved']/$reviewer['total_assigned'])*100) : 0;
            echo "<tr>";
            echo "<td>".htmlspecialchars($reviewer['name'])."</td>";
            echo "<td>".$reviewer['total_assigned']."</td>";
            echo "<td>".$reviewer['resolved']."</td>";
            echo "<td>".$resolution_rate."%</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Footer
        echo "<p style='margin-top: 20px; font-size: 11px; color: #777;'>";
        echo "Generated on ".date('Y-m-d H:i:s')." by ".$_SESSION['name'];
        echo "</p>";
        
        echo "</body>";
        echo "</html>";
        exit();
    }
}

// Ensure only admins can access
if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Get report data (only if not exporting)
$stats = [];
$result = $conn->query("SELECT 
    COUNT(*) as total_complaints,
    SUM(status = 'resolved') as resolved,
    SUM(status = 'new') as new,
    SUM(status = 'assigned') as assigned,
    SUM(status = 'in_progress') as in_progress,
    SUM(priority = 'high') as high_count,
    SUM(priority = 'medium') as medium_count,
    SUM(priority = 'low') as low_count
    FROM complaints");
$stats = $result->fetch_assoc();

// Get monthly data for chart
$monthly_data = [];
$result = $conn->query("SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as count,
    SUM(status = 'resolved') as resolved
    FROM complaints
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month");
while ($row = $result->fetch_assoc()) {
    $monthly_data[] = $row;
}

// Get reviewer performance
$reviewers = [];
$result = $conn->query("SELECT 
    u.id, u.name,
    COUNT(c.id) as total_assigned,
    SUM(c.status = 'resolved') as resolved
    FROM users u
    LEFT JOIN complaints c ON u.id = c.assigned_to
    WHERE u.role = 'reviewer' AND u.is_active = 1
    GROUP BY u.id");
while ($row = $result->fetch_assoc()) {
    $reviewers[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - ResolverIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">System Reports</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Reports</li>
                    </ol>
                </nav>
            </div>
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-download me-2"></i> Export
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="reports.php?export=pdf" target="_blank"><i class="fas fa-file-pdf me-2"></i> PDF</a></li>
                    <li><a class="dropdown-item" href="reports.php?export=excel" target="_blank"><i class="fas fa-file-excel me-2"></i> Excel</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Cases</h6>
                                <h3 class="mb-0"><?php echo $stats['total_complaints']; ?></h3>
                            </div>
                            <div class="bg-primary-light text-primary rounded p-3">
                                <i class="fas fa-clipboard-list fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Resolved</h6>
                                <h3 class="mb-0"><?php echo $stats['resolved']; ?></h3>
                                <small class="text-muted"><?php echo $stats['total_complaints'] > 0 ? round(($stats['resolved']/$stats['total_complaints'])*100) : 0; ?>% resolution rate</small>
                            </div>
                            <div class="bg-success-light text-success rounded p-3">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">High Priority</h6>
                                <h3 class="mb-0"><?php echo $stats['high_count']; ?></h3>
                                <small class="text-muted"><?php echo $stats['total_complaints'] > 0 ? round(($stats['high_count']/$stats['total_complaints'])*100) : 0; ?>% of total</small>
                            </div>
                            <div class="bg-danger-light text-danger rounded p-3">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Pending</h6>
                                <h3 class="mb-0"><?php echo $stats['new'] + $stats['assigned'] + $stats['in_progress']; ?></h3>
                                <small class="text-muted">Active cases</small>
                            </div>
                            <div class="bg-warning-light text-warning rounded p-3">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Case Trends (Last 6 Months)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Case Status</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reviewer Performance -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Reviewer Performance</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Reviewer</th>
                                <th>Assigned</th>
                                <th>Resolved</th>
                                <th>Resolution Rate</th>
                                <th>Avg. Resolution Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviewers as $reviewer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reviewer['name']); ?></td>
                                <td><?php echo $reviewer['total_assigned']; ?></td>
                                <td><?php echo $reviewer['resolved']; ?></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $reviewer['total_assigned'] > 0 ? ($reviewer['resolved']/$reviewer['total_assigned'])*100 : 0; ?>%" 
                                             aria-valuenow="<?php echo $reviewer['total_assigned'] > 0 ? ($reviewer['resolved']/$reviewer['total_assigned'])*100 : 0; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                            <?php echo $reviewer['total_assigned'] > 0 ? round(($reviewer['resolved']/$reviewer['total_assigned'])*100) : 0; ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>3.2 days</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Monthly Cases Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'" . date('M Y', strtotime($item['month'] . '-01')) . "'"; }, $monthly_data)); ?>],
                datasets: [
                    {
                        label: 'New Cases',
                        data: [<?php echo implode(',', array_column($monthly_data, 'count')); ?>],
                        borderColor: '#4361ee',
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Resolved Cases',
                        data: [<?php echo implode(',', array_column($monthly_data, 'resolved')); ?>],
                        borderColor: '#4cc9f0',
                        backgroundColor: 'rgba(76, 201, 240, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Status Pie Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Resolved', 'New', 'Assigned', 'In Progress'],
                datasets: [{
                    data: [
                        <?php echo $stats['resolved']; ?>,
                        <?php echo $stats['new']; ?>,
                        <?php echo $stats['assigned']; ?>,
                        <?php echo $stats['in_progress']; ?>
                    ],
                    backgroundColor: [
                        '#4cc9f0',
                        '#f72585',
                        '#4361ee',
                        '#f8961e'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
    </script>
</body>
</html>
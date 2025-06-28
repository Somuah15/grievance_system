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

// Handle delete complaint
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_complaint_id'])) {
    $delete_id = intval($_POST['delete_complaint_id']);
    // Ensure the complaint belongs to the logged-in user
    $stmt = $conn->prepare("DELETE FROM complaints WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $delete_id, $_SESSION['user_id']);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['message'] = "Complaint deleted successfully.";
    } else {
        $error = "Failed to delete complaint or unauthorized action.";
    }
    header("Location: complaints.php");
    exit();
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
     <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-list-task me-2"></i>My Complaints</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newComplaintModal">
                <i class="bi bi-plus-circle"></i> New Complaint
            </button>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle-fill me-1"></i><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (empty($complaints)): ?>
            <div class="alert alert-info"><i class="bi bi-info-circle me-1"></i>You haven't submitted any complaints yet.</div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-hash"></i> ID</th>
                                    <th><i class="bi bi-card-text"></i> Title</th>
                                    <th><i class="bi bi-flag"></i> Priority</th>
                                    <th><i class="bi bi-flag"></i> Status</th>
                                    <th><i class="bi bi-calendar-event"></i> Date</th>
                                    <th><i class="bi bi-eye"></i> Actions</th>
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
                                        $priority_icon = '';
                                        switch ($complaint['priority']) {
                                            case 'high': $priority_class = 'bg-danger'; $priority_icon = 'bi-exclamation-circle-fill'; break;
                                            case 'medium': $priority_class = 'bg-warning text-dark'; $priority_icon = 'bi-exclamation-triangle-fill'; break;
                                            case 'low': $priority_class = 'bg-success'; $priority_icon = 'bi-arrow-down-circle-fill'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $priority_class; ?>">
                                            <i class="bi <?php echo $priority_icon; ?> me-1"></i>
                                            <?php echo ucfirst($complaint['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        $status_icon = '';
                                        switch ($complaint['status']) {
                                            case 'new': $status_class = 'badge-new'; $status_icon = 'bi-file-earmark-plus'; break;
                                            case 'assigned': $status_class = 'badge-assigned'; $status_icon = 'bi-person-lines-fill'; break;
                                            case 'in_progress': $status_class = 'badge-in_progress'; $status_icon = 'bi-hourglass-split'; break;
                                            case 'resolved': $status_class = 'badge-resolved'; $status_icon = 'bi-patch-check-fill'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo 'badge-status-' . str_replace('_', '-', $complaint['status']); ?>">
                                            <i class="bi <?php echo $status_icon; ?> me-1"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                                    <td>
                                        <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-eye"></i> View</a>
                                        <button type="button" class="btn btn-sm btn-danger ms-1" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $complaint['id']; ?>" data-title="<?php echo htmlspecialchars($complaint['title'], ENT_QUOTES); ?>">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
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
                    <h5 class="modal-title" id="newComplaintModalLabel"><i class="bi bi-plus-circle me-1"></i>Submit New Complaint</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label"><i class="bi bi-card-text me-1"></i>Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="priority" class="form-label"><i class="bi bi-flag me-1"></i>Priority</label>
                            <select class="form-select" id="priority" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label"><i class="bi bi-chat-left-text me-1"></i>Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Cancel</button>
                        <button type="submit" name="submit_complaint" class="btn btn-primary"><i class="bi bi-send"></i> Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="POST">
            <div class="modal-header">
              <h5 class="modal-title" id="deleteModalLabel"><i class="bi bi-trash me-1 text-danger"></i>Delete Complaint</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="delete_complaint_id" id="delete_complaint_id">
              <p>Are you sure you want to delete this complaint?</p>
              <div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-1"></i> <span id="deleteComplaintTitle"></span></div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Cancel</button>
              <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Delete</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Pass complaint id and title to modal
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      var complaintId = button.getAttribute('data-id');
      var complaintTitle = button.getAttribute('data-title');
      document.getElementById('delete_complaint_id').value = complaintId;
      document.getElementById('deleteComplaintTitle').textContent = complaintTitle;
    });
    </script>
</body>
</html>
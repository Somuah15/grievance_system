<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

require_login();

// Mark notifications as read when viewing
mark_notifications_read($_SESSION['user_id']);

// Get notifications
$notifications = [];
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - ResolverIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Add Bootstrap Icons CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="/assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">   
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-bell-fill me-2"></i>Your Notifications</h2>
            <button class="btn btn-primary" id="refreshNotifications">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
        <div id="notifications-list">
        <?php if (empty($notifications)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle-fill me-2"></i>You have no notifications.
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($notifications as $notification): ?>
                <a href="<?php echo $notification['link'] ?? '#'; ?>" class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <p class="mb-1">
                            <?php 
                            // Add appropriate icon based on notification type
                            $icon = 'bi-bell';
                            if (strpos($notification['message'], 'completed') !== false) {
                                $icon = 'bi-check-circle-fill text-success';
                            } elseif (strpos($notification['message'], 'error') !== false) {
                                $icon = 'bi-exclamation-triangle-fill text-danger';
                            } elseif (strpos($notification['message'], 'warning') !== false) {
                                $icon = 'bi-exclamation-circle-fill text-warning';
                            }
                            ?>
                            <i class="bi <?php echo $icon; ?> me-2"></i>
                            <?php echo htmlspecialchars($notification['message']); ?>
                        </p>
                        <small><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></small>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('refreshNotifications').addEventListener('click', function() {
        fetch('notifications.php?ajax=1')
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newList = doc.getElementById('notifications-list');
                document.getElementById('notifications-list').innerHTML = newList.innerHTML;
            });
    });
    </script>
</body>
</html>

<?php
// AJAX handler for notifications refresh
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    // Re-run the notifications query
    $notifications = [];
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    ?>
    <?php if (empty($notifications)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle-fill me-2"></i>You have no notifications.
        </div>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($notifications as $notification): ?>
            <a href="<?php echo $notification['link'] ?? '#'; ?>" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <p class="mb-1">
                        <?php 
                        $icon = 'bi-bell';
                        if (strpos($notification['message'], 'completed') !== false) {
                            $icon = 'bi-check-circle-fill text-success';
                        } elseif (strpos($notification['message'], 'error') !== false) {
                            $icon = 'bi-exclamation-triangle-fill text-danger';
                        } elseif (strpos($notification['message'], 'warning') !== false) {
                            $icon = 'bi-exclamation-circle-fill text-warning';
                        }
                        ?>
                        <i class="bi <?php echo $icon; ?> me-2"></i>
                        <?php echo htmlspecialchars($notification['message']); ?>
                    </p>
                    <small><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></small>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php
    exit();
}
?>
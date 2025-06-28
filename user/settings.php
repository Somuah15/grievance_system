<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

// Ensure only admins can access
if ($_SESSION['role'] != 'user') {
    header("Location: ../index.php");
    exit();
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = sanitize_input($_POST['current_password']);
    $new_password = sanitize_input($_POST['new_password']);
    $confirm_password = sanitize_input($_POST['confirm_password']);
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password == $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Password changed successfully";
            } else {
                $error = "Error changing password: " . $conn->error;
            }
        } else {
            $error = "New passwords do not match";
        }
    } else {
        $error = "Current password is incorrect";
    }
}

// Handle email change
if (isset($_POST['change_email'])) {
    $new_email = sanitize_input($_POST['new_email']);
    
    $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
    $stmt->bind_param("si", $new_email, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['email'] = $new_email;
        $_SESSION['message'] = "Email changed successfully";
    } else {
        $error = "Error changing email: " . $conn->error;
    }
}

// Handle preferences save
if (isset($_POST['save_preferences'])) {
    $_SESSION['email_notifications'] = isset($_POST['email_notifications']) ? 1 : 0;
    $_SESSION['inapp_notifications'] = isset($_POST['inapp_notifications']) ? 1 : 0;
    $_SESSION['message'] = "Preferences saved.";
    header("Location: settings.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - ResolverIT</title>
     <link rel="icon" type="image/png" href="/assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <h2 class="mb-4">Settings</h2>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-white border-bottom-0">
                        <h5 class="mb-0"><i class="bi bi-key me-2"></i>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="current_password" class="form-label"><i class="bi bi-lock me-1"></i>Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label"><i class="bi bi-shield-lock me-1"></i>New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label"><i class="bi bi-shield-check me-1"></i>Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary w-100"><i class="bi bi-arrow-repeat me-1"></i>Change Password</button>
                        </form>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-header bg-white border-bottom-0">
                        <h5 class="mb-0"><i class="bi bi-envelope-at me-2"></i>Change Email</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="new_email" class="form-label"><i class="bi bi-envelope me-1"></i>New Email</label>
                                <input type="email" class="form-control" id="new_email" name="new_email" required>
                            </div>
                            <button type="submit" name="change_email" class="btn btn-primary w-100"><i class="bi bi-send me-1"></i>Change Email</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-white border-bottom-0">
                        <h5 class="mb-0"><i class="bi bi-gear me-2"></i>System Preferences</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Notifications</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="emailNotifications" name="email_notifications" value="1" <?php if(isset($_SESSION['email_notifications']) && $_SESSION['email_notifications']) echo 'checked'; ?>>
                                    <label class="form-check-label" for="emailNotifications"><i class="bi bi-envelope-fill me-1"></i>Email Notifications</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="inAppNotifications" name="inapp_notifications" value="1" <?php if(isset($_SESSION['inapp_notifications']) && $_SESSION['inapp_notifications']) echo 'checked'; ?>>
                                    <label class="form-check-label" for="inAppNotifications"><i class="bi bi-bell-fill me-1"></i>In-App Notifications</label>
                                </div>
                            </div>
                            <button type="submit" name="save_preferences" class="btn btn-primary w-100"><i class="bi bi-save me-1"></i>Save Preferences</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
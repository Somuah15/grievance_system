<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

require_role('reviewer');

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
            $hashed_password = hash_password($new_password);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviewer Settings - ResolverIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="/assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .password-strength {
            height: 5px;
            margin-top: 5px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
        }
        .password-hints {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
        .password-hints ul {
            padding-left: 20px;
            margin-bottom: 0;
        }
        .password-hints .valid {
            color: #28a745;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-gear-fill me-2"></i>Reviewer Settings</h2>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?php echo $error; ?></div>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header d-flex align-items-center">
                            <i class="bi bi-shield-lock me-2"></i>
                            <h5 class="mb-0">Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="passwordForm">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">
                                        <i class="bi bi-key me-1"></i>Current Password
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">
                                        <i class="bi bi-key-fill me-1"></i>New Password
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength mt-2">
                                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                    </div>
                                    <div class="password-hints mt-2" id="passwordHints">
                                        <small>Password must contain:</small>
                                        <ul>
                                            <li id="lengthHint">At least 8 characters</li>
                                            <li id="uppercaseHint">One uppercase letter</li>
                                            <li id="numberHint">One number</li>
                                            <li id="specialHint">One special character</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">
                                        <i class="bi bi-key-fill me-1"></i>Confirm New Password
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="mt-2" id="passwordMatch"></div>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-1"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <i class="bi bi-info-circle me-2"></i>
                            <h5 class="mb-0">Account Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-person me-1"></i>Role</label>
                                <p class="form-control-static">Reviewer</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-calendar me-1"></i>Last Password Change</label>
                                <p class="form-control-static">
                                    <?php 
                                    $stmt = $conn->prepare("SELECT password_changed_at FROM users WHERE id = ?");
                                    $stmt->bind_param("i", $_SESSION['user_id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $user = $result->fetch_assoc();
                                    echo $user['password_changed_at'] ? date('M d, Y H:i', strtotime($user['password_changed_at'])) : 'Never changed';
                                    ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-clock-history me-1"></i>Last Login</label>
                                <p class="form-control-static">
                                    <?php 
                                    $stmt = $conn->prepare("SELECT last_login FROM users WHERE id = ?");
                                    $stmt->bind_param("i", $_SESSION['user_id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $user = $result->fetch_assoc();
                                    echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never logged in';
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });

        // Password strength meter
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('passwordStrengthBar');
        const passwordHints = document.getElementById('passwordHints');
        
        newPassword.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Check length
            if (password.length >= 8) {
                strength += 25;
                document.getElementById('lengthHint').classList.add('valid');
            } else {
                document.getElementById('lengthHint').classList.remove('valid');
            }
            
            // Check uppercase
            if (/[A-Z]/.test(password)) {
                strength += 25;
                document.getElementById('uppercaseHint').classList.add('valid');
            } else {
                document.getElementById('uppercaseHint').classList.remove('valid');
            }
            
            // Check numbers
            if (/[0-9]/.test(password)) {
                strength += 25;
                document.getElementById('numberHint').classList.add('valid');
            } else {
                document.getElementById('numberHint').classList.remove('valid');
            }
            
            // Check special chars
            if (/[^A-Za-z0-9]/.test(password)) {
                strength += 25;
                document.getElementById('specialHint').classList.add('valid');
            } else {
                document.getElementById('specialHint').classList.remove('valid');
            }
            
            // Update strength bar
            strengthBar.style.width = strength + '%';
            
            // Update color
            if (strength < 50) {
                strengthBar.style.backgroundColor = '#dc3545';
            } else if (strength < 75) {
                strengthBar.style.backgroundColor = '#fd7e14';
            } else {
                strengthBar.style.backgroundColor = '#28a745';
            }
            
            // Check password match
            checkPasswordMatch();
        });
        
        confirmPassword.addEventListener('input', checkPasswordMatch);
        
        function checkPasswordMatch() {
            const matchElement = document.getElementById('passwordMatch');
            if (newPassword.value && confirmPassword.value) {
                if (newPassword.value === confirmPassword.value) {
                    matchElement.innerHTML = '<small class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Passwords match</small>';
                } else {
                    matchElement.innerHTML = '<small class="text-danger"><i class="bi bi-exclamation-circle-fill me-1"></i>Passwords do not match</small>';
                }
            } else {
                matchElement.innerHTML = '';
            }
        }
    </script>
</body>
</html>
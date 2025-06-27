<?php
include 'includes/config.php';
include 'includes/functions.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            // Simulate sending reset link
            $success = 'If this email is registered, a password reset link has been sent.';
        } else {
            $success = 'If this email is registered, a password reset link has been sent.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - ResolverIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { background: #f5f7fa; font-family: 'Poppins', sans-serif; }
        .reset-container { max-width: 420px; margin: 60px auto; }
        .reset-card { border-radius: 16px; box-shadow: 0 8px 32px rgba(67,97,238,0.10); border: none; }
        .reset-header { background: linear-gradient(135deg, #4361ee, #3f37c9); color: #fff; border-radius: 16px 16px 0 0; padding: 2rem 1.5rem; text-align: center; }
        .reset-header h2 { font-weight: 700; }
        .reset-body { padding: 2rem 1.5rem; background: #fff; border-radius: 0 0 16px 16px; }
        .form-control { height: 48px; border-radius: 8px; }
        .btn-reset { background: linear-gradient(135deg, #4361ee, #3f37c9); color: #fff; border: none; border-radius: 8px; font-weight: 600; height: 48px; }
        .btn-reset:hover { background: linear-gradient(135deg, #3f37c9, #4361ee); }
        .back-link { display: block; text-align: center; margin-top: 1.5rem; color: #4361ee; text-decoration: none; font-weight: 500; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <div class="card reset-card">
                <div class="reset-header">
                    <h2>Forgot Password?</h2>
                    <p class="mb-0">Enter your email to receive a reset link</p>
                </div>
                <div class="reset-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show animate__animated animate__shakeX mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn mb-3">
                            <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-reset">Send Reset Link</button>
                        </div>
                    </form>
                    <a href="login.php" class="back-link"><i class="fas fa-arrow-left me-1"></i> Back to Login</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

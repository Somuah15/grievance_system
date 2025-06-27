<?php
include 'includes/config.php';
include 'includes/functions.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);
    $confirm_password = sanitize_input($_POST['confirm_password']);

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Email is already registered.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user';
            $stmt = $conn->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $name, $email, $hashed_password, $role);
            if ($stmt->execute()) {
                $success = 'Registration successful! You can now <a href="index.php" class="text-primary">login</a>.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
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
    <title>Sign Up - ResolverIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body { background: #f4f6fb; }
        .signup-card { max-width: 430px; margin: 60px auto; border-radius: 1.5rem; box-shadow: 0 8px 32px rgba(60,72,100,0.12); }
        .bg-animated-gradient { background: linear-gradient(90deg, #6366f1 0%, #60a5fa 100%); }
    </style>
</head>
<body>
    <div class="container">
        <div class="signup-card bg-white p-4 p-md-5 mt-5 animate__animated animate__fadeInUp">
            <div class="text-center mb-4">
                <div class="bg-animated-gradient rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;">
                    <i class="fas fa-user-plus text-white fs-2"></i>
                </div>
                <h2 class="fw-bold mb-1">Create Account</h2>
                <p class="text-muted mb-0">Sign up to ResolverIT</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-3">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3 mb-3">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <form method="POST" action="signup.php" autocomplete="off">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="name" name="name" placeholder="Full Name" required>
                    <label for="name"><i class="fas fa-user me-2 text-primary"></i>Full Name</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="Email Address" required>
                    <label for="email"><i class="fas fa-envelope me-2 text-primary"></i>Email Address</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password"><i class="fas fa-lock me-2 text-primary"></i>Password</label>
                </div>
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                    <label for="confirm_password"><i class="fas fa-lock me-2 text-primary"></i>Confirm Password</label>
                </div>
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary btn-lg rounded-3 py-3 fw-bold shadow-primary">
                        <span class="submit-text">Sign Up</span>
                        <span class="submit-spinner spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
                <div class="text-center">
                    <span class="text-muted small">Already have an account?</span> <a href="index.php" class="text-primary small fw-bold">Login</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Form submission animation
    document.querySelector('form').addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.querySelector('.submit-text').classList.add('d-none');
        btn.querySelector('.submit-spinner').classList.remove('d-none');
    });
    </script>
</body>
</html>

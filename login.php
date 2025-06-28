<?php
include 'includes/config.php';
include 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);
    
    $stmt = $conn->prepare("SELECT id, email, password, role, name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header("Location: admin/dashboard.php");
                    break;
                case 'reviewer':
                    header("Location: reviewer/dashboard.php");
                    break;
                case 'user':
                    header("Location: user/dashboard.php");
                    break;
            }
            exit();
        } else {
            $error = "Invalid email or password";
        }
    } else {
        $error = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ResolverIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(120deg, #6366f1 0%, #60a5fa 100%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-wrapper {
            background: #fff;
            border-radius: 2rem;
            box-shadow: 0 8px 32px rgba(60,72,100,0.12);
            display: flex;
            max-width: 900px;
            width: 100%;
            overflow: hidden;
        }
        .login-illustration {
            background: linear-gradient(135deg, #6366f1 0%, #60a5fa 100%);
            color: #fff;
            flex: 1 1 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
        }
        .login-illustration img {
            max-width: 260px;
            margin-bottom: 2rem;
        }
        .login-illustration h2 {
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 1rem;
        }
        .login-illustration p {
            font-size: 1.1rem;
            opacity: 0.95;
        }
        .login-form-section {
            flex: 1 1 0;
            padding: 3rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-form-section h3 {
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #4f46e5;
        }
        .form-label {
            font-weight: 500;
            color: #6366f1;
        }
        .form-control {
            height: 48px;
            border-radius: 10px;
            border: 1px solid #e0e7ef;
            font-size: 1rem;
        }
        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99,102,241,0.12);
        }
        .btn-login {
            background: linear-gradient(135deg, #6366f1 0%, #60a5fa 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1.1rem;
            height: 48px;
            transition: all 0.2s;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(99,102,241,0.18);
        }
        .login-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }
        .login-links a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
        }
        .login-links a:hover {
            text-decoration: underline;
        }
        .alert {
            font-size: 1.05rem;
            font-weight: 500;
        }
        @media (max-width: 900px) {
            .login-wrapper { flex-direction: column; }
            .login-illustration { min-height: 220px; }
        }
        @media (max-width: 600px) {
            .login-wrapper { border-radius: 0; box-shadow: none; }
            .login-illustration, .login-form-section { padding: 2rem 1rem; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper shadow-lg">
        <div class="login-illustration d-none d-md-flex flex-column align-items-center justify-content-center">
            <img src="assets/images/logo.png" alt="Login Illustration">
            <h2>Welcome to ResolverIT</h2>
            <p>Anonymous. Secure. Empowering your voice.<br>Login to access your dashboard and track your grievances.</p>
        </div>
        <div class="login-form-section">
            <h3 class="mb-4">Sign In</h3>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show animate__animated animate__shakeX mb-4" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <form action="login.php" method="POST" autocomplete="off">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-login">Sign In</button>
                </div>
            </form>
            <div class="login-links">
                <a href="forget-password.php">Forgot password?</a>
                <a href="signup.php">Create account</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
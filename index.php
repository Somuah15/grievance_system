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
    <title>ResolverIT - Anonymous Grievance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="assets/css/style.css" rel="stylesheet">

</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="assets/images/logo.png" alt="ResolverIT Logo" class="floating">
                ResolverIT
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item ms-2">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title animate__animated animate__fadeInUp">Speak Up <span class="text-primary-light">Anonymously</span></h1>
                <p class="hero-subtitle animate__animated animate__fadeInUp animate__delay-1s">
                    A secure platform for employees to voice concerns without fear. Your identity stays protected while we ensure your issues get resolved.
                </p>
                <div class="hero-cta animate__animated animate__fadeInUp animate__delay-2s">
                    <button class="btn btn-cta" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="fas fa-paper-plane me-2"></i>Submit Grievance
                    </button>
                    <a href="#features" class="btn btn-outline-light">
                        <i class="fas fa-play-circle me-2"></i>How It Works
                    </a>
                </div>
                <div class="hero-features animate__animated animate__fadeInUp animate__delay-3s">
                    <span class="feature-badge">
                        <i class="fas fa-user-shield"></i> 100% Anonymous
                    </span>
                    <span class="feature-badge">
                        <i class="fas fa-lock"></i> End-to-End Encrypted
                    </span>
                    <span class="feature-badge">
                        <i class="fas fa-check-circle"></i> Guaranteed Resolution
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <h2 class="section-title animate__animated animate__fadeIn">How It Works</h2>
            <p class="section-subtitle animate__animated animate__fadeIn animate__delay-1s">
                Our simple three-step process ensures your concerns are heard and addressed professionally
            </p>
            
            <div class="row g-4">
                <div class="col-md-4 animate__animated animate__fadeInUp">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-user-secret"></i>
                        </div>
                        <h3 class="feature-title">Submit Anonymously</h3>
                        <p class="feature-desc">
                            Your identity remains completely hidden while you submit your concerns through our secure platform.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 animate__animated animate__fadeInUp delay-1">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 class="feature-title">Professional Review</h3>
                        <p class="feature-desc">
                            Our qualified team reviews each submission and assigns it to the appropriate department for resolution.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 animate__animated animate__fadeInUp delay-2">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">Track Progress</h3>
                        <p class="feature-desc">
                            Monitor the status of your complaint in real-time and receive updates on the resolution process.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 about-content animate__animated animate__fadeInLeft">
                    <h2 class="section-title text-start">Why Choose ResolverIT</h2>
                    <p class="mb-4">
                        We've created a safe space for employees to voice concerns without fear of retaliation. Our system ensures complete anonymity while maintaining accountability in the resolution process.
                    </p>
                    
                    <div class="mt-4">
                        <div class="benefit-item animate__animated animate__fadeInLeft delay-1">
                            <div class="benefit-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="benefit-text">
                                <h5>Complete Protection</h5>
                                <p>Your identity is never revealed to anyone in the organization</p>
                            </div>
                        </div>
                        <div class="benefit-item animate__animated animate__fadeInLeft delay-2">
                            <div class="benefit-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="benefit-text">
                                <h5>Timely Resolution</h5>
                                <p>Strict timelines ensure your concerns are addressed promptly</p>
                            </div>
                        </div>
                        <div class="benefit-item animate__animated animate__fadeInLeft delay-3">
                            <div class="benefit-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="benefit-text">
                                <h5>Real-time Updates</h5>
                                <p>Get notified at every stage of the resolution process</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 animate__animated animate__fadeInRight">
                    <img src="assets/images/secure-illustration.svg" alt="Secure Illustration" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="text-center mb-4">
                <a href="#" class="footer-logo">ResolverIT</a>
            </div>
            <div class="footer-links">
                <a href="#" class="footer-link">Home</a>
                <a href="#features" class="footer-link">Features</a>
                <a href="#about" class="footer-link">About</a>
                <a href="#" class="footer-link">Privacy</a>
                <a href="#" class="footer-link">Terms</a>
            </div>
            <p class="copyright">&copy; <?php echo date('Y'); ?> ResolverIT. All rights reserved.</p>
        </div>
    </footer>

    <!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 overflow-hidden">
            <!-- Animated gradient header -->
            <div class="modal-header position-relative p-0" style="height: 180px;">
                <div class="position-absolute w-100 h-100 bg-animated-gradient"></div>
                <div class="position-relative z-1 w-100 h-100 d-flex flex-column justify-content-center align-items-center">
                    <h5 class="modal-title text-white fs-3 fw-bold mb-2">Welcome Back</h5>
                    <p class="text-white-50 mb-0">Sign in to your ResolverIT account</p>
                </div>
                <button type="button" class="btn-close btn-close-white position-absolute top-3 end-3 z-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Login body with floating card effect -->
            <div class="modal-body px-4 py-5" style="margin-top: -40px;">
                <div class="position-relative z-2 bg-white rounded-4 shadow-sm p-4" style="transform: translateY(-40px);">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <div><?php echo $error; ?></div>
                            </div>
                            <button type="button" class="btn-close position-absolute top-50 end-0 translate-middle-y me-2" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form action="login.php" method="POST">
                        <!-- Email input with floating label -->
                        <div class="form-floating mb-4">
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0 ps-3">
                                    <i class="fas fa-envelope text-primary"></i>
                                </span>
                                <input type="email" class="form-control border-start-0 ps-2" id="email" name="email" placeholder="Enter your email" required>
                                <label for="email" class="ps-5">Email Address</label>
                            </div>
                        </div>
                        
                        <!-- Password input with floating label -->
                        <div class="form-floating mb-4">
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0 ps-3">
                                    <i class="fas fa-lock text-primary"></i>
                                </span>
                                <input type="password" class="form-control border-start-0 ps-2" id="password" name="password" placeholder="Enter your password" required>
                                <label for="password" class="ps-5">Password</label>
                            </div>
                        </div>
                        
                        <!-- Submit button with loading animation -->
                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-primary btn-lg rounded-3 py-3 fw-bold shadow-primary">
                                <span class="submit-text">Sign In</span>
                                <span class="submit-spinner spinner-border spinner-border-sm d-none" role="status"></span>
                            </button>
                        </div>
                        
                        <!-- Forgot password link -->
                        <div class="text-center">
                            <a href="#" class="text-decoration-none text-muted small hover-underline" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
                                <i class="fas fa-key me-1"></i> Forgot password?
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Form submission animation
    document.querySelector('#loginModal form').addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.querySelector('.submit-text').classList.add('d-none');
        btn.querySelector('.submit-spinner').classList.remove('d-none');
        });

        // Animation on scroll
        const animateOnScroll = function() {
            const elements = document.querySelectorAll('.animate__animated');
            
            elements.forEach(element => {
                const elementPosition = element.getBoundingClientRect().top;
                const screenPosition = window.innerHeight / 1.2;
                
                if (elementPosition < screenPosition) {
                    const animationClass = element.classList[1];
                    element.classList.add(animationClass);
                }
            });
        };

        window.addEventListener('scroll', animateOnScroll);
        // Trigger once on page load
        animateOnScroll();

        // Input focus effects
    document.querySelectorAll('#loginModal .form-control').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.parentElement.querySelector('label').style.color = '#4F46E5';
            this.parentElement.querySelector('.input-group-text').style.color = '#4F46E5';
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.parentElement.querySelector('label').style.color = '#6B7280';
                this.parentElement.querySelector('.input-group-text').style.color = '';
            }
        });
    });
    </script>
</body>
</html>
<?php 
include 'includes/config.php';
include 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    
    <!-- Modern Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Modern CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            --secondary: #f43f5e;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
            --gray-light: #e2e8f0;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
            color: var(--dark);
            line-height: 1.6;
            background-color: var(--light);
            overflow-x: hidden;
        }
        
        /* Modern Glassmorphism Effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        }
        
        /* Modern Buttons */
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        /* Modern Navbar */
        .navbar {
            padding: 1rem 0;
            transition: all 0.3s ease;
        }
        
        .navbar.scrolled {
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 0.5rem 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary);
            display: flex;
            align-items: center;
        }
        
        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }
        
        /* Hero Section */
        .hero {
            position: relative;
            padding: 6rem 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: url('assets/images/grievance.png') no-repeat center right;
            background-size: contain;
            opacity: 0.1;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--gray);
            max-width: 600px;
            margin-bottom: 2rem;
        }
        
        /* Feature Cards */
        .feature-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            height: 100%;
            transition: all 0.3s ease;
            border: 1px solid var(--gray-light);
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: white;
            font-size: 1.5rem;
        }
        
        .feature-title {
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        /* Testimonials */
        .testimonial-card {
            border-radius: 16px;
            padding: 2rem;
            height: 100%;
            position: relative;
            background: white;
            border: 1px solid var(--gray-light);
        }
        
        .testimonial-card::before {
            content: '"';
            position: absolute;
            top: 1rem;
            left: 1.5rem;
            font-size: 4rem;
            color: var(--primary-light);
            opacity: 0.2;
            font-family: serif;
            line-height: 1;
        }
        
        /* Login Modal */
        .login-modal {
            border-radius: 16px;
            overflow: hidden;
            border: none;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            padding: 2rem;
            color: white;
            text-align: center;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--gray-light);
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25);
        }
        
        /* Floating Animation */
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }
        
        .floating {
            animation: floating 6s ease-in-out infinite;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
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
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title animate__animated animate__fadeInUp">Speak Up <span class="text-primary">Anonymously</span></h1>
                    <p class="hero-subtitle animate__animated animate__fadeInUp animate__delay-1s">
                        A secure platform for employees to voice concerns without fear. Your identity stays protected while we ensure your issues get resolved.
                    </p>
                    <div class="hero-cta animate__animated animate__fadeInUp animate__delay-2s">
                        <button class="btn btn-primary btn-lg me-3" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fas fa-paper-plane me-2"></i>Submit Grievance
                        </button>
                        <a href="#features" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-play-circle me-2"></i>How It Works
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block animate__animated animate__fadeIn">
                    <img src="assets/images/grieve.png" alt="Hero Illustration" class="img-fluid floating" style="animation-delay: 0.5s;">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-light">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="section-title animate__animated animate__fadeIn">How It Works</h2>
                <p class="section-subtitle text-muted animate__animated animate__fadeIn animate__delay-1s">
                    Our simple three-step process ensures your concerns are heard and addressed professionally
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4 animate__animated animate__fadeInUp">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-user-secret"></i>
                        </div>
                        <h3 class="feature-title">Submit Anonymously</h3>
                        <p class="feature-desc text-muted">
                            Your identity remains completely hidden while you submit your concerns through our secure platform.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 animate__animated animate__fadeInUp animate__delay-1s">
                    <div class="feature-card">
                        <div class="feature-icon bg-gradient-danger">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 class="feature-title">Professional Review</h3>
                        <p class="feature-desc text-muted">
                            Our qualified team reviews each submission and assigns it to the appropriate department for resolution.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 animate__animated animate__fadeInUp animate__delay-2s">
                    <div class="feature-card">
                        <div class="feature-icon bg-gradient-success">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">Track Progress</h3>
                        <p class="feature-desc text-muted">
                            Monitor the status of your complaint in real-time and receive updates on the resolution process.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <img src="assets/images/about-illustration.svg" alt="About Illustration" class="img-fluid animate__animated animate__fadeInLeft">
                </div>
                <div class="col-lg-6">
                    <h2 class="section-title text-start animate__animated animate__fadeInRight">Why Choose ResolverIT</h2>
                    <p class="text-muted mb-4 animate__animated animate__fadeInRight animate__delay-1s">
                        We've created a safe space for employees to voice concerns without fear of retaliation. Our system ensures complete anonymity while maintaining accountability in the resolution process.
                    </p>
                    
                    <div class="mt-4">
                        <div class="d-flex mb-4 animate__animated animate__fadeInRight animate__delay-1s">
                            <div class="me-4">
                                <div class="feature-icon" style="width: 50px; height: 50px;">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                            </div>
                            <div>
                                <h5>Complete Protection</h5>
                                <p class="text-muted mb-0">Your identity is never revealed to anyone in the organization</p>
                            </div>
                        </div>
                        <div class="d-flex mb-4 animate__animated animate__fadeInRight animate__delay-2s">
                            <div class="me-4">
                                <div class="feature-icon" style="width: 50px; height: 50px;">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                            <div>
                                <h5>Timely Resolution</h5>
                                <p class="text-muted mb-0">Strict timelines ensure your concerns are addressed promptly</p>
                            </div>
                        </div>
                        <div class="d-flex animate__animated animate__fadeInRight animate__delay-3s">
                            <div class="me-4">
                                <div class="feature-icon" style="width: 50px; height: 50px;">
                                    <i class="fas fa-bell"></i>
                                </div>
                            </div>
                            <div>
                                <h5>Real-time Updates</h5>
                                <p class="text-muted mb-0">Get notified at every stage of the resolution process</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="py-5 bg-dark text-white">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="section-title animate__animated animate__fadeIn">Trusted by Thousands</h2>
                <p class="section-subtitle text-light animate__animated animate__fadeIn animate__delay-1s">
                    What our users say about ResolverIT
                </p>
            </div>
            
            <div class="splide" aria-label="Testimonials">
                <div class="splide__track">
                    <ul class="splide__list">
                        <li class="splide__slide">
                            <div class="testimonial-card">
                                <div class="d-flex align-items-center mb-4">
                                    <img src="assets/images/testimonial1.jpg" alt="User" class="rounded-circle me-3" width="60" height="60">
                                    <div>
                                        <h5 class="mb-0">Priya S.</h5>
                                        <small class="text-muted">Employee</small>
                                    </div>
                                </div>
                                <p class="mb-0">"ResolverIT made it easy to raise my concern without fear. The process was smooth and I got updates at every step!"</p>
                            </div>
                        </li>
                        <li class="splide__slide">
                            <div class="testimonial-card">
                                <div class="d-flex align-items-center mb-4">
                                    <img src="assets/images/testimonial2.jpg" alt="User" class="rounded-circle me-3" width="60" height="60">
                                    <div>
                                        <h5 class="mb-0">Rahul M.</h5>
                                        <small class="text-muted">Reviewer</small>
                                    </div>
                                </div>
                                <p class="mb-0">"The dashboard is intuitive and helps me resolve complaints efficiently. Highly recommended!"</p>
                            </div>
                        </li>
                        <li class="splide__slide">
                            <div class="testimonial-card">
                                <div class="d-flex align-items-center mb-4">
                                    <img src="assets/images/testimonial3.jpg" alt="User" class="rounded-circle me-3" width="60" height="60">
                                    <div>
                                        <h5 class="mb-0">Amit K.</h5>
                                        <small class="text-muted">Admin</small>
                                    </div>
                                </div>
                                <p class="mb-0">"ResolverIT ensures transparency and accountability in our organization. The best grievance system we've used."</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container py-4">
            <div class="row text-center">
                <div class="col-md-3 mb-4 mb-md-0">
                    <h2 class="display-4 fw-bold stat-count" data-count="10000">0</h2>
                    <p class="mb-0">Grievances Resolved</p>
                </div>
                <div class="col-md-3 mb-4 mb-md-0">
                    <h2 class="display-4 fw-bold stat-count" data-count="100">0</h2>
                    <p class="mb-0">Anonymous</p>
                </div>
                <div class="col-md-3 mb-4 mb-md-0">
                    <h2 class="display-4 fw-bold stat-count" data-count="24">0</h2>
                    <p class="mb-0">Support</p>
                </div>
                <div class="col-md-3">
                    <h2 class="display-4 fw-bold stat-count" data-count="4.9">0</h2>
                    <p class="mb-0">User Rating</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-5 bg-light">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="section-title">Frequently Asked Questions</h2>
                <p class="section-subtitle text-muted">
                    Everything you need to know about ResolverIT
                </p>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item border-0 mb-3">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button shadow-none rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    Is my identity really anonymous?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, your identity is never revealed to anyone in the organization. We use advanced encryption and anonymization techniques to ensure complete confidentiality.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 mb-3">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button shadow-none rounded-3 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    How do I track my complaint?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    You can log in to your dashboard to view the current status of your complaint. We'll also send you email notifications at each stage of the resolution process.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 mb-3">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button shadow-none rounded-3 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Can I submit a complaint without creating an account?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    For full tracking and updates, we recommend creating an account. However, you may contact us directly for anonymous feedback if you prefer not to create an account.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 cta-section" style="background: linear-gradient(90deg, #6366f1 0%, #4f46e5 100%); color: #fff;">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="display-5 fw-bold mb-4" style="color: #fff; text-shadow: 0 2px 8px rgba(0,0,0,0.18);">Ready to Speak Up Safely?</h2>
                    <p class="lead mb-5" style="color: #fff; text-shadow: 0 2px 8px rgba(0,0,0,0.18);">Join thousands of employees who have found their voice through ResolverIT</p>
                    <div class="d-flex justify-content-center gap-3">
                        <button class="btn btn-light btn-lg px-4" data-bs-toggle="modal" data-bs-target="#loginModal">
                            Submit Grievance
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content login-modal">
                <div class="login-header">
                    <h3 class="mb-0">Welcome Back</h3>
                    <p class="mb-0">Login to your ResolverIT account</p>
                </div>
                <div class="login-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            <a href="forget-password.php" class="text-primary">Forgot password?</a>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                            <span class="submit-text">Login</span>
                            <span class="spinner-border spinner-border-sm d-none submit-spinner" role="status" aria-hidden="true"></span>
                    </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-5 bg-dark text-white">
        <div class="container py-4">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <a href="#" class="d-flex align-items-center mb-3 text-decoration-none">
                        <img src="assets/images/logo.png" alt="ResolverIT Logo" height="40" class="me-2">
                        <span class="fs-4 fw-bold">ResolverIT</span>
                    </a>
                    <p class="text-muted">The anonymous grievance resolution platform that protects your identity while ensuring your voice is heard.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                    <h5 class="mb-3">Company</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">About</a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Careers</a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Blog</a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Press</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                    <h5 class="mb-3">Product</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Features</a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Pricing</a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Security</a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">FAQ</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5 class="mb-3">Subscribe to our newsletter</h5>
                    <p class="text-muted">Monthly digest of what's new and exciting from us.</p>
                    <form class="d-flex gap-2">
                        <input type="email" class="form-control" placeholder="Email address" required>
                        <button class="btn btn-primary" type="submit">Subscribe</button>
                    </form>
                </div>
            </div>
            <hr class="my-4 border-secondary">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> ResolverIT. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item"><a href="#" class="text-muted text-decoration-none">Privacy Policy</a></li>
                        <li class="list-inline-item"><a href="#" class="text-muted text-decoration-none">Terms of Service</a></li>
                        <li class="list-inline-item"><a href="#" class="text-muted text-decoration-none">Contact Us</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <a href="#" class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-4 shadow" id="backToTop" style="z-index:1050; display: none;">
        <i class="fas fa-arrow-up"></i>
    </a>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js"></script>
    <script>
        // Initialize Splide slider for testimonials
        new Splide('.splide', {
            type: 'loop',
            perPage: 1,
            autoplay: true,
            interval: 5000,
            pauseOnHover: false,
            breakpoints: {
                768: {
                    perPage: 1
                }
            }
        }).mount();
        
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
            
            // Show/hide back to top button
            const backToTop = document.getElementById('backToTop');
            if (window.scrollY > 300) {
                backToTop.style.display = 'flex';
            } else {
                backToTop.style.display = 'none';
            }
        });
        
        // Back to top button
        document.getElementById('backToTop').addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
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
        
        // Animated stats counter
        function animateCount(el, target, duration) {
            let start = 0;
            let startTime = null;
            const isFloat = String(target).includes('.');
            function step(timestamp) {
                if (!startTime) startTime = timestamp;
                const progress = Math.min((timestamp - startTime) / duration, 1);
                const value = isFloat ? (progress * target).toFixed(1) : Math.floor(progress * target);
                el.textContent = isFloat ? value + '★' : value + (target == 100 ? '%' : (target == 24 ? '/7' : '+'));
                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    el.textContent = isFloat ? target + '★' : target + (target == 100 ? '%' : (target == 24 ? '/7' : '+'));
                }
            }
            requestAnimationFrame(step);
        }
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.stat-count').forEach(function(el) {
                const target = parseFloat(el.getAttribute('data-count'));
                animateCount(el, target, 1800);
            });
        });
    </script>
</body>
</html>
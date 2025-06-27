<?php
// logout.php
session_start();

// If not confirmed, show confirmation page
if (!isset($_GET['confirm'])) {
    $_SESSION['logout_referer'] = $_SERVER['HTTP_REFERER'] ?? 'admin/dashboard.php';
    header('Content-Type: text/html');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirm Logout</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            body {
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                background: linear-gradient(135deg, #f8fafc 0%, #e0e7ef 100%);
            }
            .logout-container {
                max-width: 400px;
                padding: 2.5rem 2rem 2rem 2rem;
                background: #fff;
                border-radius: 1rem;
                text-align: center;
                border: 1px solid #e2e8f0;
            }
            .logout-icon {
                font-size: 3rem;
                color: #f43f5e;
                margin-bottom: 1rem;
            }
            .btn-danger {
                background: linear-gradient(90deg, #f43f5e 0%, #e11d48 100%);
                border: none;
            }
            .btn-danger:hover {
                background: linear-gradient(90deg, #e11d48 0%, #f43f5e 100%);
            }
            .btn-secondary {
                background: #f3f4f6;
                color: #374151;
                border: none;
            }
            .btn-secondary:hover {
                background: #e5e7eb;
                color:rgb(76, 88, 115);
            }
        </style>
    </head>
    <body>
        <div class="logout-container">
            <div class="logout-icon">
                <i class="bi bi-box-arrow-right"></i>
            </div>
            <h4 class="mb-3">Confirm Logout</h4>
            <p class="mb-4 text-muted">Are you sure you want to log out of your account?</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="?confirm=1" class="btn btn-danger px-4"><i class="bi bi-box-arrow-right me-2"></i>Yes, Logout</a>
                <a href="<?php echo htmlspecialchars($_SESSION['logout_referer']); ?>" class="btn btn-secondary px-4">Cancel</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// If confirmed, proceed with logout
session_unset();
session_destroy();

// Prevent caching of the page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to login page
header("Location: index.php");
exit();
?>
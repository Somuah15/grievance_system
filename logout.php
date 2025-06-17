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
        <style>
            body {
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                background-color: #f8f9fa;
            }
            .logout-container {
                max-width: 400px;
                padding: 2rem;
                background: white;
                border-radius: 0.5rem;
                box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="logout-container">
            <h4>Confirm Logout</h4>
            <p class="mb-4">Are you sure you want to log out?</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="?confirm=1" class="btn btn-danger">Yes, Logout</a>
                <a href="<?php echo htmlspecialchars($_SESSION['logout_referer']); ?>" class="btn btn-secondary">Cancel</a>
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
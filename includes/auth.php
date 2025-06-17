<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authentication functions
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: ../login.php");
        exit();
    }
}

function require_role($role) {
    require_login();
    if ($_SESSION['role'] != $role) {
        header("Location: ../unauthorized.php");
        exit();
    }
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function current_user_role() {
    return $_SESSION['role'] ?? null;
}

// Authorization check for complaints
function can_access_complaint($complaint_id) {
    global $conn;
    
    // Admins can access all complaints
    if ($_SESSION['role'] == 'admin') {
        return true;
    }
    
    $stmt = $conn->prepare("SELECT user_id, assigned_to FROM complaints WHERE id = ?");
    $stmt->bind_param("i", $complaint_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $complaint = $result->fetch_assoc();
        
        // Users can access their own complaints
        if ($_SESSION['role'] == 'user' && $complaint['user_id'] == $_SESSION['user_id']) {
            return true;
        }
        
        // Reviewers can access assigned complaints
        if ($_SESSION['role'] == 'reviewer' && $complaint['assigned_to'] == $_SESSION['user_id']) {
            return true;
        }
    }
    
    return false;
}

// Password hashing
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Verify password
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}
?>
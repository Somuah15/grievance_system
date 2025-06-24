<div class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand d-flex align-items-center">
            <img src="../../assets/images/logo.png" alt="ResolverIT">
            <span class="ms-2">ResolverIT</span>
        </a>
    </div>
    
    <div class="user-profile">
        <div class="user-avatar">
            <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
        </div>
        <h5 class="user-name"><?php echo $_SESSION['name']; ?></h5>
        <span class="user-role badge bg-primary-light mt-1">
            <?php echo ucfirst($_SESSION['role']); ?>
        </span>
    </div>
    
    <!-- Scrollable Navigation -->
    <div class="sidebar-nav-container">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <?php if ($_SESSION['role'] == 'user'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'complaints.php' ? 'active' : ''; ?>" href="complaints.php">
                        <i class="fas fa-envelope"></i>
                        <span>My Complaints</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>" href="notifications.php">
                        <i class="fas fa-bell"></i>
                        <span>Notifications</span>
                    </a>
                </li>
            <?php elseif ($_SESSION['role'] == 'reviewer'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'inbox.php' ? 'active' : ''; ?>" href="inbox.php">
                        <i class="fas fa-inbox"></i>
                        <span>Inbox</span>
                        <span class="badge bg-danger rounded-pill ms-auto">3</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'checked.php' ? 'active' : ''; ?>" href="checked.php">
                        <i class="fas fa-tasks"></i>
                        <span>In Progress</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'resolved.php' ? 'active' : ''; ?>" href="resolved.php">
                        <i class="fas fa-check-circle"></i>
                        <span>Resolved</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
                        <i class="fas fa-users"></i>
                        <span>User Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'complaints.php' ? 'active' : ''; ?>" href="complaints.php">
                        <i class="fas fa-tasks"></i>
                        <span>Case Management</span>
                        <span class="badge bg-danger rounded-pill ms-auto">5</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'create.php' ? 'active' : ''; ?>" href="create.php">
                        <i class="fas fa-plus-circle"></i>
                        <span>Create New</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Fixed Logout Button -->
    <div class="sidebar-footer">
        <a class="nav-link text-danger" href="../logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>
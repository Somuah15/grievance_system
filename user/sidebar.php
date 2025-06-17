<div class="sidebar">
    <div class="p-4">
        <h4 class="text-center mb-4">ResolverIT</h4>
        <div class="text-center mb-4">
            <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                <span class="fs-3"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></span>
            </div>
            <h5 class="mt-3 mb-0"><?php echo $_SESSION['name']; ?></h5>
            <small class="text-muted"><?php echo ucfirst($_SESSION['role']); ?></small>
        </div>
    </div>
    
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link active" href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <?php if ($_SESSION['role'] == 'user'): ?>
            <li class="nav-item">
                <a class="nav-link" href="complaints.php">
                    <i class="bi bi-envelope"></i> My Complaints
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="notifications.php">
                    <i class="bi bi-bell"></i> Notifications
                </a>
            </li>
        <?php elseif ($_SESSION['role'] == 'reviewer'): ?>
            <li class="nav-item">
                <a class="nav-link" href="inbox.php">
                    <i class="bi bi-inbox"></i> Inbox
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="checked.php">
                    <i class="bi bi-check-circle"></i> In Progress
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="resolved.php">
                    <i class="bi bi-check2-all"></i> Resolved
                </a>
            </li>
        <?php else: ?>
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="complaints.php">
                    <i class="bi bi-envelope"></i> Complaints
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="create.php">
                    <i class="bi bi-plus-circle"></i> Create
                </a>
            </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link" href="settings.php">
                <i class="bi bi-gear"></i> Settings
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../logout.php">
                <i class="bi bi-box-arrow-left"></i> Logout
            </a>
        </li>
    </ul>
</div>
<?php
// Get the current page from the URL
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$currentPage = basename($currentPath, '.php');
if (empty($currentPage) || $currentPage === 'index') {
    $currentPage = 'dashboard';
}

// Get user role from JWT token
$isAdmin = false;
if (isset($_COOKIE['jwt_token'])) {
    try {
        $token = $_COOKIE['jwt_token'];
        $payload = json_decode(base64_decode(explode('.', $token)[1]), true);
        $isAdmin = isset($payload['role']) && $payload['role'] === 'admin';
    } catch (Exception $e) {
        // Token is invalid or malformed
        $isAdmin = false;
    }
}
?>

<nav class="nav flex-column sidebar-nav">
    <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="<?= url('dashboard') ?>">
        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
    </a>
    <a class="nav-link <?php echo $currentPage === 'quizzes' ? 'active' : ''; ?>" href="<?= url('quizzes') ?>">
        <i class="fas fa-question-circle me-2"></i>Quizzes
    </a>
    <a class="nav-link <?php echo $currentPage === 'performance' ? 'active' : ''; ?>" href="<?= url('performance') ?>">
        <i class="fas fa-chart-line me-2"></i>Performance
    </a>
    <a class="nav-link <?php echo $currentPage === 'leaderboard' ? 'active' : ''; ?>" href="<?= url('leaderboard') ?>">
        <i class="fas fa-trophy me-2"></i>Leaderboard
    </a>

    <?php if ($isAdmin): ?>
    <div class="sidebar-divider"></div>
    <h6 class="sidebar-heading">Admin</h6>
    <a class="nav-link <?php echo $currentPage === 'admin_dashboard' ? 'active' : ''; ?>" href="<?= url('admin_dashboard') ?>">
        <i class="fas fa-cog me-2"></i>Admin Dashboard
    </a>
    <a class="nav-link <?php echo $currentPage === 'admin_users' ? 'active' : ''; ?>" href="<?= url('admin_users') ?>">
        <i class="fas fa-users me-2"></i>Manage Users
    </a>
    <a class="nav-link <?php echo $currentPage === 'admin_quizzes' ? 'active' : ''; ?>" href="<?= url('admin_quizzes') ?>">
        <i class="fas fa-question-circle me-2"></i>Manage Quizzes
    </a>
    <?php endif; ?>
</nav>

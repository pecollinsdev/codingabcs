<?php
use App\Core\Session;

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    Session::start();
}

// Get current URL path
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= htmlspecialchars(BASE_URL) ?>">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | Coding ABCs' : 'Coding ABCs' ?></title>

    <script>
        window.BASE_URL = "<?= BASE_URL ?>";
    </script>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL) ?>/css/styles.css">
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Favicon -->
    <link rel="icon" href="<?= htmlspecialchars(BASE_URL) ?>/images/favicon.ico" type="image/x-icon">
</head>
<body>

<!-- Header -->
<header class="bg-dark text-white py-3">
    <div class="container-fluid d-flex justify-content-between align-items-center px-4">
        <h1 class="m-0 fs-4">
            <a href="<?= htmlspecialchars(BASE_URL) ?>" class="text-white text-decoration-none">Coding ABCs</a>
        </h1>
        
        <!-- Right Section: Menu Button & Logout -->
        <div class="d-flex align-items-center gap-3"> 
            <!-- Sidebar Toggle Button for Mobile -->
            <div class="d-block d-md-none">
                <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#mobileSidebar"
                    aria-controls="mobileSidebar" aria-expanded="false" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i> Menu
                </button>
            </div>

            <!-- Navigation Links -->
            <nav role="navigation">
                <ul class="list-unstyled d-flex m-0 align-items-center">
                    <?php if (Session::has('user_id')): ?>
                        <li>
                            <a class="btn btn-danger btn-sm px-3 py-2" href="<?= htmlspecialchars(BASE_URL) ?>/auth/logout">Logout</a>
                        </li>
                    <?php else: ?>
                        <!-- Hide on mobile, show on medium (md) screens and larger -->
                        <li class="d-none d-md-inline">
                            <a class="text-white text-decoration-none me-4" href="<?= htmlspecialchars(BASE_URL) ?>/auth/login">Login</a>
                        </li>
                        <li class="d-none d-md-inline">
                            <a class="text-white text-decoration-none" href="<?= htmlspecialchars(BASE_URL) ?>/auth/register">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
</header>


<!-- Page Wrapper -->
<div class="main-wrapper">

<!-- Mobile Sidebar (Dropdown) -->
<div class="collapse d-md-none bg-dark p-3" id="mobileSidebar">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/dashboard" class="nav-link text-light">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/quiz/quizzes" class="nav-link text-light">
                <i class="fas fa-list me-2"></i> Quizzes
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/stats/performance" class="nav-link text-light">
                <i class="fas fa-chart-line me-2"></i> Performance
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/stats/leaderboard" class="nav-link text-light">
                <i class="fas fa-trophy me-2"></i> Leaderboard
            </a>
        </li>
    </ul>
</div>

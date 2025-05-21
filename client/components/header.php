<?php
// components/header.php
?>
<nav class="navbar fixed-top navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand" href="home">
      <img src="/codingabcs/client/assets/images/logo.svg" alt="Coding<ABCs>" height="40" class="d-inline-block align-text-top">
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <i class="fas fa-bars"></i>
    </button>

    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav ms-auto align-items-center">
        <?php if (!empty($userData)): ?>
          <!-- Mobile Navigation -->
          <div class="d-lg-none w-100">
            <div class="mobile-nav">
              <a class="nav-link" href="dashboard">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
              </a>
              <a class="nav-link" href="quizzes">
                <i class="fas fa-question-circle"></i>
                <span>Quizzes</span>
              </a>
              <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i>
                <span>Performance</span>
              </a>
              <a class="nav-link" href="leaderboard">
                <i class="fas fa-trophy"></i>
                <span>Leaderboard</span>
              </a>
              <?php if ($userData['is_admin']): ?>
                <div class="mobile-nav-divider"></div>
                <div class="mobile-nav-heading">Admin</div>
                <a class="nav-link" href="admin_dashboard">
                  <i class="fas fa-cog"></i>
                  <span>Admin Dashboard</span>
                </a>
                <a class="nav-link" href="admin_users">
                  <i class="fas fa-users"></i>
                  <span>Manage Users</span>
                </a>
                <a class="nav-link" href="admin_quizzes">
                  <i class="fas fa-question-circle"></i>
                  <span>Manage Quizzes</span>
                </a>
              <?php endif; ?>
            </div>
          </div>

          <!-- Profile Dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
              <i class="fas fa-user-circle"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="dashboard"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><button id="themeToggle" class="dropdown-item"><i class="fas fa-moon me-2"></i>Theme</button></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="#" id="logoutBtn"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <button id="themeToggle" class="btn btn-link nav-link theme-switch">
              <i class="fas fa-moon"></i>
              <span class="ms-1">Theme</span>
            </button>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="login">
              <i class="fas fa-sign-in-alt"></i>
              <span class="ms-1">Login</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="register">
              <i class="fas fa-user-plus"></i>
              <span class="ms-1">Register</span>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

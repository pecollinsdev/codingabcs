<?php
// components/layout.php

define('ASSET_BASE', '/codingabcs/client/assets');
define('BASE_URL', '/codingabcs/client/public');  // URL root for links

// URL helper for building absolute paths
function url(string $path): string {
    return BASE_URL . '/' . ltrim($path, '/');
}

function getTheme(): string {
    return $_COOKIE['theme'] ?? 'light';
}

$currentTheme = getTheme(); // "light" or "dark"

// Get initial theme from cookie or localStorage
$initialTheme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';

// Get user state from JWT token
$userData = null;
if (isset($_COOKIE['jwt_token'])) {
    try {
        $token = $_COOKIE['jwt_token'];
        $payload = json_decode(base64_decode(explode('.', $token)[1]), true);
        $userData = [
            'id' => $payload['id'] ?? null,
            'email' => $payload['email'] ?? null,
            'role' => $payload['role'] ?? 'user',
            'is_admin' => ($payload['role'] ?? '') === 'admin'
        ];
    } catch (Exception $e) {
        // Token is invalid or malformed
        $userData = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="<?= htmlspecialchars($initialTheme) ?>-theme">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Coding ABCs &mdash; <?= ucfirst(htmlspecialchars($page)) ?></title>

  <!-- Bootstrap & Font Awesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" rel="stylesheet">

  <!-- Global CSS -->
  <link href="<?= ASSET_BASE ?>/css/layout.css" rel="stylesheet">
  <link href="<?= ASSET_BASE ?>/css/theme.css" rel="stylesheet">

  <!-- Page‑specific CSS -->
  <?php
    $cssMap = [
      'home'            => 'home.css',
      'login'           => 'auth.css',
      'register'        => 'auth.css',
      'quiz'            => 'quiz.css',
      'quizzes'         => 'quizzes.css',
      'results'         => 'results.css',
      'dashboard'       => 'dashboard.css',
      'admin_dashboard' => 'admin.css',
      'admin_users'     => 'admin.css',
      'admin_quizzes'   => 'admin.css',
      'admin_quiz_edit' => 'admin.css',
      'leaderboard'     => 'leaderboard.css',
      'performance'     => 'performance.css',
    ];
    if (isset($cssMap[$page])): ?>
      <link href="<?= ASSET_BASE ?>/css/<?= $cssMap[$page] ?>" rel="stylesheet">
  <?php endif; ?>

  <!-- Theme script (load early) -->
  <script src="<?= ASSET_BASE ?>/js/theme.js"></script>
</head>
<body>
  <?php include __DIR__ . '/header.php'; ?>

  <div class="app-container">
    <?php if ($showSidebar): ?>
      <aside class="sidebar-desktop d-none d-lg-block">
        <?php include __DIR__ . '/sidebar.php'; ?>
      </aside>
    <?php endif; ?>

    <main class="main-content <?= $showSidebar ? 'with-sidebar' : '' ?>">
      <?php
        // Include the correct page template
        $file = dirname(__DIR__) . '/pages/' . $page . '.php';
        if (is_readable($file)) {
            include $file;
        } else {
            include dirname(__DIR__) . '/pages/404.php';
        }
      ?>
    </main>

    <?php if ($showFooter): ?>
      <footer id="footer-container">
        <?php include __DIR__ . '/footer.php'; ?>
      </footer>
    <?php endif; ?>
  </div>

  <?php include __DIR__ . '/popup.php'; ?>

  <!-- JS Libraries -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Global JS -->
  <script src="<?= ASSET_BASE ?>/js/theme.js"></script>
  <script src="<?= ASSET_BASE ?>/js/components.js"></script>
  <script src="<?= ASSET_BASE ?>/js/validation.js"></script>
  <script src="<?= ASSET_BASE ?>/js/auth.js"></script>
  <script src="<?= ASSET_BASE ?>/js/api.js"></script>
  <script src="<?= ASSET_BASE ?>/js/main.js"></script>

  <!-- Page‑specific JS -->
  <?php
    $jsMap = [
      'results'         => 'results.js',
      'quiz'            => 'quiz.js',
      'quizzes'         => 'quizzes.js',
      'login'           => 'auth.js',
      'register'        => 'auth.js',
      'dashboard'       => 'dashboard.js',
      'leaderboard'     => 'leaderboard.js',
      'performance'     => 'performance.js',
      'admin_dashboard' => 'admin.js',
      'admin_users'     => 'admin.js',
      'admin_quizzes'   => 'admin.js',
      'admin_quiz_edit' => 'admin.js',
      'admin_quiz_create' => 'admin.js',
    ];
    if (isset($jsMap[$page])): ?>
      <script src="<?= ASSET_BASE ?>/js/<?= $jsMap[$page] ?>"></script>
  <?php endif; ?>
</body>
</html>

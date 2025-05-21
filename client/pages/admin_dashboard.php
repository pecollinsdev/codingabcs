<?php
// Check if user is admin from JWT token
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

if (!$isAdmin) {
    header('Location: ' . url('login'));
    exit;
}

require_once __DIR__ . '/../assets/php/card_builder.php';
$cardBuilder = new CardBuilder();
?>

<div class="container-fluid py-4 admin-dashboard">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Admin Dashboard</h1>
        </div>
    </div>

    <div class="row">
        <!-- Users Management Card -->
        <div class="col-md-6 mb-4">
            <?php
            echo $cardBuilder->build([
                'title' => '<i class="fas fa-users"></i> User Management',
                'content' => '<p class="card-text">Manage user accounts, roles, and permissions.</p>
                            <div class="d-grid gap-2">
                                <a href="' . url('admin/users') . '" class="btn btn-primary">
                                    <i class="fas fa-cog me-2"></i>Manage Users
                                </a>
                            </div>',
                'classes' => 'h-100',
                'hover' => false
            ]);
            ?>
        </div>

        <!-- Quizzes Management Card -->
        <div class="col-md-6 mb-4">
            <?php
            echo $cardBuilder->build([
                'title' => '<i class="fas fa-question-circle"></i> Quiz Management',
                'content' => '<p class="card-text">Create, edit, and manage quizzes.</p>
                            <div class="d-grid gap-2">
                                <a href="' . url('admin/quizzes') . '" class="btn btn-primary">
                                    <i class="fas fa-cog me-2"></i>Manage Quizzes
                                </a>
                            </div>',
                'classes' => 'h-100',
                'hover' => false
            ]);
            ?>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mt-4">
        <div class="col-12">
            <h2 class="mb-4">Quick Stats</h2>
        </div>
        <div class="col-md-4 mb-4">
            <?php
            echo $cardBuilder->build([
                'title' => 'Total Users',
                'content' => '<p class="card-text display-4" id="totalUsers">Loading...</p>',
                'classes' => 'h-100',
                'hover' => false
            ]);
            ?>
        </div>
        <div class="col-md-4 mb-4">
            <?php
            echo $cardBuilder->build([
                'title' => 'Total Quizzes',
                'content' => '<p class="card-text display-4" id="totalQuizzes">Loading...</p>',
                'classes' => 'h-100',
                'hover' => false
            ]);
            ?>
        </div>
        <div class="col-md-4 mb-4">
            <?php
            echo $cardBuilder->build([
                'title' => 'Active Users',
                'content' => '<p class="card-text display-4" id="activeUsers">Loading...</p>',
                'classes' => 'h-100',
                'hover' => false
            ]);
            ?>
        </div>
    </div>
</div> 
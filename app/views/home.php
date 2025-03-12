<?php require_once '../app/views/layouts/header.php'; ?>

<!-- Main Content Wrapper -->
<div class="container text-center mt-5">
    <h1>Welcome to Coding ABCs</h1>
    <p class="lead">Your go-to platform for mastering coding fundamentals through interactive quizzes.</p>
    
    <div class="mt-4">
        <a href="<?= BASE_URL ?>/auth/login" class="btn btn-primary btn-lg">Continue</a>
        <a href="<?= BASE_URL ?>/auth/register" class="btn btn-outline-secondary btn-lg">Sign Up</a>
    </div>
</div>

<div class="container mt-5">
    <h2 class="text-center">Why Choose Coding ABCs?</h2>
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card shadow-sm p-3 mb-4 bg-white rounded">
                <div class="card-body">
                    <h3 class="card-title">Interactive Quizzes</h3>
                    <p class="card-text">Test your knowledge with our wide range of coding quizzes and challenges.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm p-3 mb-4 bg-white rounded">
                <div class="card-body">
                    <h3 class="card-title">Track Your Progress</h3>
                    <p class="card-text">Monitor your improvement over time with our performance tracking system.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm p-3 mb-4 bg-white rounded">
                <div class="card-body">
                    <h3 class="card-title">Learn at Your Own Pace</h3>
                    <p class="card-text">Access quizzes anytime and enhance your coding skills at your convenience.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../app/views/layouts/footer.php'; ?>

<footer class="bg-light text-center text-lg-start mt-5">
    <div class="text-center p-3">
        © 2025 Coding ABCs. All rights reserved.
    </div>
</footer>


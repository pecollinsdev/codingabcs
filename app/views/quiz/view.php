<?php require_once '../app/views/layouts/header.php'; ?>

<!-- Main Content Wrapper with Flexbox -->
<div class="container d-flex align-items-center justify-content-center" style="min-height: 80vh;">
    <!-- Card with Responsive Width -->
    <div class="card shadow-sm border-0 w-100 w-md-50">
        <div class="card-body text-center">
            <!-- Quiz Title -->
            <h2 class="card-title"><?= htmlspecialchars($quiz['title']) ?></h2>
            
            <!-- Quiz Description -->
            <p class="card-text text-muted"><?= nl2br(htmlspecialchars($quiz['description'])) ?></p>

            <!-- Content to make the page take up more space -->
            <p class="fw-bold mb-4">Are you ready to begin?</p>
            
            <!-- Start Quiz and Cancel Buttons -->
            <div class="d-flex justify-content-center gap-3 mb-4">
                <a href="<?= BASE_URL ?>/quiz/start/<?= $quiz['id'] ?>" class="btn btn-primary">
                    Yes, Start Quiz!
                </a>
                <a href="<?= BASE_URL ?>/quiz/quizzes" class="btn btn-danger">
                    Cancel
                </a>
            </div>

            <!-- Additional Content -->
            <p class="text-muted">Once you start this quiz, you must complete it in one session. Progress is not saved, quiz will restart if you exit and return later.</p>
        </div>
    </div>
</div>

<?php require_once '../app/views/layouts/footer.php'; ?>

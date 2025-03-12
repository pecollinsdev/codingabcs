<?php require_once '../app/views/layouts/header.php'; ?>

<div class="d-flex flex-column flex-md-row">
    <!-- Sidebar -->
    <?php require_once '../app/views/layouts/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="content flex-grow-1 p-4">
        <h2>Dashboard</h2>

        <!-- Recent Quiz Stats -->
        <h4 class="mt-4">Recent Quiz Stats</h4>
        <?php if (!empty($recentQuizzes)): ?>
            <div class="row">
                <?php foreach ($recentQuizzes as $quiz): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($quiz['title']) ?></h5>
                                <p class="card-text"><strong>Score:</strong> <?= $quiz['score'] ?></p>
                                <p class="card-text">
                                    <strong>Attempt Date:</strong> <?= date("F j, Y, g:i a", strtotime($quiz['attempt_date'])) ?>
                                </p>
                                <p class="text-muted"><strong>Total Questions:</strong> <?= $quiz['total_questions'] ?? 'N/A' ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">No recent quiz attempts.</p>
        <?php endif; ?>

        <!-- New Quizzes (Limit to 3) -->
        <h4 class="mt-4">New Quizzes</h4>
        <?php 
            $limitedNewQuizzes = array_slice($newQuizzes, 0, 3);
        ?>
        <?php if (!empty($limitedNewQuizzes)): ?>
            <div class="row">
                <?php foreach ($limitedNewQuizzes as $quiz): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($quiz['title']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($quiz['description']) ?></p>
                                <a href="<?= BASE_URL ?>/quiz/view/<?= $quiz['id'] ?>" class="btn btn-primary">Start Quiz</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">No new quizzes available.</p>
        <?php endif; ?>

        <!-- Popular Quizzes (Limit to 3 and filter by attempts) -->
        <h4 class="mt-4">Popular Quizzes</h4>
        <?php 
            $filteredPopularQuizzes = array_filter($popularQuizzes, fn($quiz) => $quiz['attempt_count'] > 0);
            $limitedPopularQuizzes = array_slice($filteredPopularQuizzes, 0, 3);
        ?>
        <?php if (!empty($limitedPopularQuizzes)): ?>
            <div class="row">
                <?php foreach ($limitedPopularQuizzes as $quiz): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($quiz['title']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($quiz['description']) ?></p>
                                <p class="text-muted">Attempts: <?= $quiz['attempt_count'] ?></p>
                                <a href="<?= BASE_URL ?>/quiz/view/<?= $quiz['id'] ?>" class="btn btn-primary">Start Quiz</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">No popular quizzes available.</p>
        <?php endif; ?>

    </div>
</div>

<?php require_once '../app/views/layouts/footer.php'; ?>

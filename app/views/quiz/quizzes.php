<?php require_once '../app/views/layouts/header.php'; ?>

<div class="d-flex flex-column flex-md-row">
    <!-- Sidebar -->
    <?php require_once '../app/views/layouts/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="content flex-grow-1 p-4">
        <h2>Available Quizzes</h2>

        <!-- Search and Filter Form -->
        <form method="GET" action="<?= BASE_URL ?>/quiz/quizzes" class="mb-4 d-flex gap-2">
            <input type="text" name="search" class="form-control" placeholder="Search quizzes..." 
                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">

            <select name="category" class="form-select">
                <option value="all" <?= empty($_GET['category']) ? 'selected' : '' ?>>All Categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= htmlspecialchars($category) ?>" 
                        <?= (isset($_GET['category']) && $_GET['category'] === $category) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if (!empty($quizzes)): ?>
            <div class="row">
                <?php foreach ($quizzes as $quiz): ?>
                    <div class="col-md-4 mb-4 quiz-item" data-title="<?= strtolower($quiz['title']) ?>" 
                         data-description="<?= strtolower($quiz['description']) ?>"
                         data-category="<?= strtolower($quiz['category']) ?>">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($quiz['title']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($quiz['description']) ?></p>
                                <a href="<?= BASE_URL ?>/quiz/view/<?= $quiz['id'] ?>" class="btn btn-primary">
                                    Start Quiz
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">No quizzes available at the moment.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../app/views/layouts/footer.php'; ?>

<script src="<?= BASE_URL ?>/js/main.js"></script>
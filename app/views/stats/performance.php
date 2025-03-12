<?php require_once '../app/views/layouts/header.php'; ?>

<div class="d-flex flex-column flex-md-row">
    <!-- Sidebar -->
    <?php require_once '../app/views/layouts/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="content flex-grow-1 p-4">
        <h2 class="mb-4">My Performance</h2>

        <!-- ✅ Overall Stats -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body text-center">
                <h4>Overall Stats</h4>
                <p><strong>Total Quizzes Taken:</strong> <?= $overallStats['total_attempts'] ?? 0 ?></p>
                <p><strong>Average Score:</strong> <?= round($overallStats['avg_score'] ?? 0, 2) ?></p>
                <p><strong>Best Score:</strong> <?= $overallStats['best_score'] ?? 0 ?></p>
                <p><strong>Worst Score:</strong> <?= $overallStats['worst_score'] ?? 0 ?></p>
            </div>
        </div>

        <!-- ✅ Recent Quiz Attempts -->
        <h4 class="mb-3">Recent Quiz Attempts</h4>
        <?php if (!empty($recentStats)): ?>
            <ul class="list-group mb-4">
                <?php foreach ($recentStats as $attempt): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <strong><?= htmlspecialchars($attempt['title']) ?></strong>
                        <span>Score: <?= $attempt['score'] ?>/<?= $attempt['total_questions'] ?? 'N/A' ?> 
                            <small class="text-muted d-block">
                                (<?= date("F j, Y, g:i A", strtotime($attempt['attempt_date'])) ?>)
                            </small>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-muted">No recent attempts available.</p>
        <?php endif; ?>

        <!-- ✅ Full Quiz History with Pagination -->
        <h4 class="mb-3">Full Quiz History</h4>
        <?php if (!empty($history)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Quiz Title</th>
                            <th>Score</th>
                            <th>Date</th>
                            <th>View Results</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $attempt): ?>
                            <tr>
                                <td><?= htmlspecialchars($attempt['title']) ?></td>
                                <td><?= $attempt['score'] ?>/<?= $attempt['total_questions'] ?? 'N/A' ?></td>
                                <td><?= date("F j, Y, g:i A", strtotime($attempt['attempt_date'])) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/quiz/result/<?= $attempt['quiz_id'] ?>/<?= $attempt['attempt_id'] ?>" 
                                        class="btn btn-primary btn-sm">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Controls -->
            <?php if (isset($totalPages) && $totalPages > 1): ?>
                <nav class="d-flex justify-content-center mt-3">
                    <ul class="pagination">
                        <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= BASE_URL ?>/stats/performance?page=<?= max(1, $currentPage - 1) ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= BASE_URL ?>/stats/performance?page=<?= $i ?>"> <?= $i ?> </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= BASE_URL ?>/stats/performance?page=<?= min($totalPages, $currentPage + 1) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-muted">No quiz history available.</p>
        <?php endif; ?>
    </div>
</div>


<?php require_once '../app/views/layouts/footer.php'; ?>



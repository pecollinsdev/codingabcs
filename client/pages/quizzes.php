<?php

require_once __DIR__ . '/../assets/php/card_builder.php';
require_once __DIR__ . '/../assets/php/api_client.php';

// Initialize card builder and API client
$cardBuilder = new CardBuilder();
$apiClient   = new ApiClient();

// Add button styles
echo $cardBuilder->addButtonStyles();

// Check authentication
if (!isset($_COOKIE['jwt_token'])) {
    echo '
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            Please <a href="index.php?page=login">login</a> to view quizzes.
        </div>
    ';
    exit;
}

// Read filters from query
$search     = $_GET['search']     ?? '';
$difficulty = $_GET['difficulty'] ?? '';
$category   = $_GET['category']   ?? '';
$sort       = $_GET['sort']       ?? 'newest';
$limit      = (int)($_GET['limit']  ?? 9);
$offset     = (int)($_GET['offset'] ?? 0);

// Fetch quizzes list
$response = $apiClient->get('quizzes', [
    'search'     => $search,
    'difficulty' => $difficulty,
    'category'   => $category,
    'sort'       => $sort,
    'limit'      => $limit,
    'offset'     => $offset
]);

if (isset($response['error'])) {
    echo '
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            Error fetching quizzes: ' . htmlspecialchars($response['error']) . '
        </div>
    ';
    $quizzes = [];
    $total   = 0;
} else {
    $quizzes = $response['data']['quizzes'] ?? [];
    $total   = $response['data']['total'] ?? 0;
}

// Fetch all progress data in a single call
$progressResponse = $apiClient->get('quizzes/progress');
$progressData = $progressResponse['data'] ?? [];

// Enrich quizzes with progress data
foreach ($quizzes as &$quiz) {
    if (isset($progressData[$quiz['id']])) {
        $quiz['has_progress'] = true;
        $quiz['current_question'] = $progressData[$quiz['id']]['current_question'];
        $quiz['last_updated'] = $progressData[$quiz['id']]['last_updated'];
    } else {
        $quiz['has_progress'] = false;
    }
}
unset($quiz);

// Build unique category list
$categories = [];
foreach ($quizzes as $q) {
    if (!empty($q['category']) && !in_array($q['category'], $categories, true)) {
        $categories[] = $q['category'];
    }
}
sort($categories);

// Pass the fetched quizzes data to JavaScript
echo '<script>';
echo 'window.initialQuizzesData = ' . json_encode([
    'quizzes' => $quizzes,
    'total' => $total,
    'filters' => [
        'search' => $search,
        'difficulty' => $difficulty,
        'category' => $category,
        'sort' => $sort,
        'limit' => $limit,
        'offset' => $offset
    ]
]) . ';';
echo '</script>';
?>

<div class="quizzes-container">
    <?php echo $cardBuilder->addButtonStyles(); ?>
    
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Quizzes</h1>
    </div>

    <!-- Search & Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <?php
            echo $cardBuilder->build([
                'title'   => 'Search & Filter',
                'content' => '
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" id="searchInput" class="form-control" placeholder="Search quizzes…" value="' . htmlspecialchars($search) . '">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select id="categoryFilter" class="form-select">
                                <option value="">All Categories</option>
                                ' . implode('', array_map(function($cat) use ($category) {
                                    return '<option value="' . htmlspecialchars($cat) . '"' . ($cat === $category ? ' selected' : '') . '>' . htmlspecialchars($cat) . '</option>';
                                }, $categories)) . '
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="difficultyFilter" class="form-select">
                                <option value="">All Difficulties</option>
                                <option value="beginner"' . ($difficulty === 'beginner' ? ' selected' : '') . '>Beginner</option>
                                <option value="intermediate"' . ($difficulty === 'intermediate' ? ' selected' : '') . '>Intermediate</option>
                                <option value="advanced"' . ($difficulty === 'advanced' ? ' selected' : '') . '>Advanced</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="sortBy" class="form-select">
                                <option value="newest"' . ($sort === 'newest' ? ' selected' : '') . '>Newest First</option>
                                <option value="oldest"' . ($sort === 'oldest' ? ' selected' : '') . '>Oldest First</option>
                                <option value="difficulty_asc"' . ($sort === 'difficulty_asc' ? ' selected' : '') . '>Difficulty ↑</option>
                                <option value="difficulty_desc"' . ($sort === 'difficulty_desc' ? ' selected' : '') . '>Difficulty ↓</option>
                            </select>
                        </div>
                    </div>
                ',
                'classes' => 'search-filter-card',
                'hover'   => false
            ]);
            ?>
        </div>
    </div>

    <!-- Quizzes Grid -->
    <div class="row g-4" id="quizzesGrid">
        <?php if (empty($quizzes)): ?>
            <?php
            echo $cardBuilder->build([
                'title'   => 'No Quizzes Available',
                'content' => '
                    <div class="text-center py-5">
                        <i class="fas fa-question-circle fa-3x mb-3 text-muted"></i>
                        <h4 class="text-muted">No quizzes available</h4>
                        <p class="text-muted">Check back later for new quizzes.</p>
                    </div>
                ',
                'classes' => 'col-12',
                'hover'   => false
            ]);
            ?>
        <?php else: ?>
            <?php foreach ($quizzes as $quiz): ?>
                <?php
                $buttons = '<div class="quiz-buttons">';
                // Start button
                $buttons .= '<button
                    type="button"
                    class="btn btn-primary start-quiz-btn"
                    data-quiz-id="' . $quiz['id'] . '"
                    data-quiz-title="' . htmlspecialchars($quiz['title']) . '"
                    data-quiz-description="' . htmlspecialchars($quiz['description'] ?? 'No description available.') . '">
                    <i class="fas fa-play me-2"></i>Start Quiz
                </button>';

                // Resume button if progress exists
                if (!empty($quiz['has_progress'])) {
                    $qnum = $quiz['current_question'] + 1;
                    $time = date('M j, Y g:i A', $quiz['last_updated']);
                    $buttons .= $cardBuilder->primaryButton(
                        'Resume (Q' . $qnum . ')',
                        '#',
                        'fas fa-redo',
                        'resume-quiz-btn'
                    );
                    $buttons .= '<small class="text-muted text-center d-block mt-1">Last updated: ' . $time . '</small>';
                }

                $buttons .= '</div>';

                echo $cardBuilder->build([
                    'title'   => htmlspecialchars($quiz['title']),
                    'content' => '
                        <div class="quiz-info">
                            <p><i class="fas fa-layer-group me-1"></i>' . htmlspecialchars($quiz['category']) . '</p>
                            <p><i class="fas fa-signal me-1"></i>' . ucfirst(htmlspecialchars($quiz['level'])) . '</p>
                            <p><i class="fas fa-question-circle me-1"></i>' . $quiz['questions_count'] . ' questions</p>
                        </div>
                        ' . $buttons,
                    'classes' => 'col-md-4 quiz-card',
                    'hover'   => false,
                    'shadow'  => true,
                    'border'  => true
                ]);
                ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total > $limit): ?>
        <?php
        $totalPages  = ceil($total / $limit);
        $currentPage = floor($offset / $limit) + 1;
        ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="pagination-container" id="paginationContainer">
                    <nav aria-label="Quiz pagination">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="#" data-page="<?= $currentPage-1 ?>" aria-label="Previous">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i === 1 || $i === $totalPages || ($i >= $currentPage - 2 && $i <= $currentPage + 2)): ?>
                                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                        <a class="page-link" href="#" data-page="<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php elseif ($i === $currentPage - 3 || $i === $currentPage + 3): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="#" data-page="<?= $currentPage+1 ?>" aria-label="Next">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Start & Resume Modals -->
<div class="modal fade" id="startQuizModal" tabindex="-1" aria-labelledby="startQuizModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="startQuizModalLabel">Start Quiz</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <h4 class="mb-3" id="modalQuizTitle"></h4>
        <div class="quiz-description text-muted mb-3" id="modalQuizDescription"></div>
        <h6 class="mb-3">Ready to start this quiz?</h6>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Cancel</button>
        <a href="#" id="startQuizBtn" class="btn btn-primary"><i class="fas fa-play me-2"></i>Start</a>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="resumeQuizModal" tabindex="-1" aria-labelledby="resumeQuizModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="resumeQuizModalLabel">Resume Quiz</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Resume "<strong id="modalResumeTitle"></strong>" at question <span id="modalResumeQuestion"></span>?</p>
        <p class="text-muted">Or would you like to start over?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Cancel</button>
        <a href="#" id="restartQuizBtn" class="btn btn-outline-primary"><i class="fas fa-sync me-2"></i>Restart</a>
        <a href="#" id="resumeQuizBtn" class="btn btn-primary"><i class="fas fa-redo me-2"></i>Resume</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>

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

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1>Quiz Management</h1>
            <a href="<?= url('admin/quiz_create') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New Quiz
            </a>
        </div>
    </div>

    <!-- Search and Filter -->
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
                                <input type="text" id="quizSearch" class="form-control" placeholder="Search quizzes...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="quizFilter">
                                <option value="all">All Quizzes</option>
                                <option value="active">Active Only</option>
                                <option value="inactive">Inactive Only</option>
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

    <!-- Quizzes Table -->
    <div class="row">
        <div class="col-12">
            <div class="table-responsive">
                <table class="table" id="quizzesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th class="d-none d-md-table-cell">Category</th>
                            <th class="d-none d-md-table-cell">Questions</th>
                            <th class="d-none d-md-table-cell">Status</th>
                            <th class="d-none d-md-table-cell">Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Quizzes will be loaded dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Alert Modal -->
<div class="modal fade" id="alertModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="alertMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmButton">Confirm</button>
            </div>
        </div>
    </div>
</div>
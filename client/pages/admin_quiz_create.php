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
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1>Create New Quiz</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= url('admin_quizzes') ?>">Quizzes</a></li>
                    <li class="breadcrumb-item active">Create Quiz</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="createQuizForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select a category</option>
                                    <option value="Web Development">Web Development</option>
                                    <option value="Object Oriented Programming">Object Oriented Programming</option>
                                    <option value="Database & SQL">Database & SQL</option>
                                    <option value="Programming Languages">Programming Languages</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="level" class="form-label">Difficulty Level</label>
                                <select class="form-select" id="level" name="level" required>
                                    <option value="beginner">Beginner</option>
                                    <option value="intermediate">Intermediate</option>
                                    <option value="advanced">Advanced</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="timeLimit" class="form-label">Time Limit (minutes)</label>
                                <input type="number" class="form-control" id="timeLimit" name="time_limit" min="1" value="30" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="is_active" required>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= url('admin_quizzes') ?>" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Quiz</button>
                        </div>
                    </form>

                    <div id="questionsContainer" style="display: none;">
                        <h4 class="mb-4">Add Questions</h4>
                        <!-- Questions will be added here dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= ASSET_BASE ?>/js/admin_quiz_create.js"></script>

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
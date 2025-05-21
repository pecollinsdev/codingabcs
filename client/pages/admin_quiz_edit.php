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

$quizId = $_GET['id'] ?? 0;
if (!$quizId) {
    header('Location: ' . url('admin_quizzes'));
    exit;
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1>Edit Quiz</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= url('admin_quizzes') ?>">Quizzes</a></li>
                    <li class="breadcrumb-item active">Edit Quiz</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="editQuizForm" data-quiz-id="<?= $quizId ?>">
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
                            <div class="col-md-6 mb-3">
                                <label for="level" class="form-label">Difficulty Level</label>
                                <select class="form-select" id="level" name="level" required>
                                    <option value="beginner">Beginner</option>
                                    <option value="intermediate">Intermediate</option>
                                    <option value="advanced">Advanced</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="is_active" required>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= url('admin_quizzes') ?>" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Questions Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Questions</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#questionModal">
                        <i class="fas fa-plus me-2"></i>Add Question
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="questionsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Question</th>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Questions will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Question Modal -->
<div class="modal fade" id="questionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="questionForm">
                    <input type="hidden" id="questionId">
                    <div class="mb-3">
                        <label for="questionText" class="form-label">Question Text</label>
                        <textarea class="form-control" id="questionText" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="questionType" class="form-label">Question Type</label>
                        <select class="form-select" id="questionType" required>
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="coding">Coding Question</option>
                        </select>
                    </div>
                    <div id="answersSection">
                        <div class="mb-3">
                            <label class="form-label">Answers</label>
                            <div id="answersContainer">
                                <!-- Answers will be added here -->
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addAnswerBtn">
                                <i class="fas fa-plus me-1"></i>Add Answer
                            </button>
                        </div>
                    </div>
                    <div id="codingSection" style="display: none;">
                        <div class="mb-3">
                            <label for="language" class="form-label">Programming Language</label>
                            <select class="form-select" id="language" required>
                                <option value="">Select language</option>
                                <option value="javascript">JavaScript</option>
                                <option value="python">Python</option>
                                <option value="java">Java</option>
                                <option value="php">PHP</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="starterCode" class="form-label">Starter Code</label>
                            <textarea class="form-control" id="starterCode" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="expectedOutput" class="form-label">Expected Output</label>
                            <textarea class="form-control" id="expectedOutput" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="hiddenInput" class="form-label">Hidden Input (Optional)</label>
                            <textarea class="form-control" id="hiddenInput" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveQuestionBtn">Save Question</button>
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

<script src="<?= ASSET_BASE ?>/js/admin_quiz_edit.js"></script> 
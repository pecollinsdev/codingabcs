<?php require_once '../app/views/layouts/header.php'; ?>

<!-- Main Content Wrapper -->
<div class="container mt-5 quiz-wrapper">
    <h2 class="mb-4"><?php echo isset($quiz['title']) ? htmlspecialchars($quiz['title']) : 'Quiz Title Not Found'; ?></h2>
    <p class="text-muted"><?php echo isset($quiz['description']) ? nl2br(htmlspecialchars($quiz['description'])) : 'No description available.'; ?></p>

    <form id="quizForm" action="<?php echo BASE_URL; ?>/quiz/submit" method="POST">
        <input type="hidden" name="quiz_id" value="<?php echo isset($quiz['id']) ? $quiz['id'] : ''; ?>">

        <?php if (!empty($questions)): ?>
            <?php foreach ($questions as $question): ?>
                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($question['question_text']); ?></h5>

                        <?php if (!empty($question['answers'])): ?>
                            <?php foreach ($question['answers'] as $answer): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" 
                                           name="answers[<?php echo $question['question_id']; ?>]" 
                                           value="<?php echo $answer['answer_id']; ?>" 
                                           id="answer_<?php echo $answer['answer_id']; ?>" required>
                                    <label class="form-check-label" for="answer_<?php echo $answer['answer_id']; ?>">
                                        <?php echo htmlspecialchars($answer['answer_text']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-danger">No answers available for this question.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="text-center mb-4">
                <button type="submit" class="btn btn-primary">Submit Quiz</button>
                <button type="button" class="btn btn-danger ms-3" id="cancelQuizBtn">Cancel</button>
            </div>
        <?php else: ?>
            <p class="text-muted">No questions available for this quiz.</p>
        <?php endif; ?>
    </form>
</div>

<!-- Cancel Confirmation Modal -->
<div class="modal fade" id="cancelConfirmModal" tabindex="-1" aria-labelledby="cancelConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelConfirmLabel">Cancel Quiz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to cancel? Your progress will not be saved, and you will have to restart the quiz.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Go Back</button>
                <a href="<?php echo BASE_URL; ?>/quiz/quizzes" class="btn btn-danger">Yes, Cancel</a>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('quizForm').addEventListener('submit', function(event) {
        event.preventDefault();
    
        let formData = new FormData(this);
    
        fetch("<?php echo BASE_URL; ?>/quiz/submit", {
            method: "POST",
            body: formData
        })
        .then(response => response.text()) // Log the raw response before parsing
        .then(data => {
            console.log("Raw Server Response:", data);
            try {
                let jsonData = JSON.parse(data); // Convert to JSON
                if (jsonData.status === "success") {
                    window.location.href = jsonData.redirect;
                } else {
                    alert(jsonData.message);
                }
            } catch (error) {
                console.error("JSON Parsing Error:", error);
                alert("Unexpected response from server. Check the console for details.");
            }
        })
        .catch(error => {
            console.error("Error submitting quiz:", error);
            alert("There was an error submitting the quiz. Please try again later.");
        });
    });

    // Show Bootstrap modal when Cancel button is clicked
    document.getElementById("cancelQuizBtn").addEventListener("click", function() {
        let cancelModal = new bootstrap.Modal(document.getElementById("cancelConfirmModal"));
        cancelModal.show();
    });
</script>

<?php require_once '../app/views/layouts/footer.php'; ?>

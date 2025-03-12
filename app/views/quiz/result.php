<?php require_once '../app/views/layouts/header.php'; ?>

<!-- Main Content Wrapper -->
<div class="container mt-5 quiz-wrapper">
    <h2 class="mb-4">Quiz Results: <?= htmlspecialchars($quiz['title']) ?></h2>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body text-center">
            <h4>Your Score: <span class="text-primary"><?= $performance['score'] ?></span> / <?= count($questions) ?></h4>
            <p class="text-muted">You answered <?= $performance['score'] ?> questions correctly out of <?= count($questions) ?>.</p>
        </div>
    </div>

    <h4 class="mb-3">Review Your Answers</h4>

    <?php foreach ($questions as $question): ?>
        <div class="card shadow-sm mb-3 border-0">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($question['question_text']) ?></h5>

                <p><strong>Correct Answer(s):</strong> 
                    <?php 
                        $correctAnswers = isset($question['correct_answer']) ? [$question['correct_answer']] : [];
                        echo implode(", ", array_map(fn($ans) => htmlspecialchars($ans), $correctAnswers));
                    ?>
                </p>

                <p><strong>Your Answer(s):</strong> 
                    <?php 
                        $userAnswers = array_filter($answers, function($answer) use ($question) {
                            return isset($answer['question_id']) && $answer['question_id'] == $question['question_id'];
                        });

                        if (!empty($userAnswers)) {
                            foreach ($userAnswers as $userAnswer) {
                                echo htmlspecialchars($userAnswer['selected_answer'] ?? 'N/A') . " ";
                                echo ($userAnswer['is_correct']) ? 
                                    '<span class="text-success">✔ Correct</span><br>' : 
                                    '<span class="text-danger">✘ Incorrect</span><br>';
                            }
                        } else {
                            echo '<span class="text-warning">⚠ Not Answered</span>';
                        }
                    ?>
                </p>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="text-center">
        <a href="<?= BASE_URL ?>/quiz/quizzes" class="btn btn-primary">Take Another Quiz</a>
        <a href="<?= BASE_URL ?>" class="btn btn-secondary">Return to Dashboard</a>
    </div>
</div>

<?php require_once '../app/views/layouts/footer.php'; ?>

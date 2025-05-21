<?php
session_start();

// Page settings
$page = 'quiz';
$showSidebar = false;
$showFooter  = false;

// Backend utilities
require_once __DIR__ . '/../assets/php/quiz_builder.php';
require_once __DIR__ . '/../assets/php/api_client.php';
require_once '../assets/php/card_builder.php';

// Initialize
$quizBuilder = new QuizBuilder();
$apiClient   = new ApiClient();
$cardBuilder = new CardBuilder();

// Determine IDs
$quizId         = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentQuestion = isset($_GET['question']) ? (int)$_GET['question'] : 0;

// Fetch data
$quiz      = $apiClient->get("quizzes/{$quizId}")['data'] ?? [];
$questions = $apiClient->get("quizzes/{$quizId}/questions")['data']['questions'] ?? [];

// Prepare initial data for JS
$_SESSION['quiz_progress'][$quizId] = $_SESSION['quiz_progress'][$quizId] ?? [];
$initialData = [
    'title'           => $quiz['title'] ?? 'Quiz',
    'quizId'          => $quizId,
    'questions'       => $questions,
    'currentQuestion' => $currentQuestion,
    'answers'         => $_SESSION['quiz_progress'][$quizId]['answers'] ?? [],
    'timeLimit'       => $quiz['time_limit'] ?? 0
];

// Add button styles
echo $cardBuilder->addButtonStyles();
?>

<main class="quiz-container-wrapper quiz-page">
    <?php
        // Directly render the QuizBuilder output; it includes its own container
        echo $quizBuilder->build([
            'title'           => $quiz['title'] ?? 'Loading Quiz...',
            'quiz_id'         => $quizId,
            'questions'       => $questions,
            'timeLimit'       => $quiz['time_limit'] ?? 0,
            'showTimer'       => true,
            'showProgress'    => true,
            'currentQuestion' => $currentQuestion
        ]);
        
        // Add Monaco Editor scripts
        echo $quizBuilder->addMonacoScripts();
    ?>
</main>

<script>
// Initialize profile dropdown
document.addEventListener('DOMContentLoaded', function() {
    const profileDropdown = document.getElementById('profileDropdown');
    if (profileDropdown) {
        new bootstrap.Dropdown(profileDropdown);
    }
});
</script> 
<?php
session_start();

$page        = 'results';
$showSidebar = true;
$showFooter  = true;

// 1) Parse quiz & attempt IDs from URL
$uri       = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts     = explode('/', trim($uri, '/'));
$quizId    = (int) end($parts);
$attemptId = isset($_GET['attempt_id']) ? (int) $_GET['attempt_id'] : 0;

// Validate IDs
if ($quizId <= 0 || $attemptId <= 0) {
    header('Location: /codingabcs/client/public/dashboard');
    exit;
}

// 2) Bootstrap API client and card builder
require_once __DIR__ . '/../assets/php/api_client.php';
require_once __DIR__ . '/../assets/php/card_builder.php';
$apiClient = new ApiClient();
$cardBuilder = new CardBuilder();

// 3) Fetch the specific attempt
try {
    $attemptResponse = $apiClient->get("quizzes/{$quizId}/attempts/{$attemptId}");
    
    if (isset($attemptResponse['error'])) {
        throw new Exception($attemptResponse['error']);
    }
    
    // Extract the attempt data correctly
    $attemptData = $attemptResponse['data'] ?? $attemptResponse;

    if (!$attemptData) {
        throw new Exception('No attempt data found');
    }

    // Get the actual attempt object
    $attempt = $attemptData['attempt'] ?? $attemptData;
} catch (Exception $e) {
    header('Location: /codingabcs/client/public/dashboard?error=attempt_not_found');
    exit;
}

// 4) Fetch questions for this quiz
try {
    $questionsResp = $apiClient->get("quizzes/{$quizId}/questions");
    
    if (isset($questionsResp['error'])) {
        throw new Exception($questionsResp['error']);
    }
    
    // Extract questions data correctly
    $questionsData = $questionsResp['data'] ?? $questionsResp;
    $questions = $questionsData['questions'] ?? [];

    if (empty($questions)) {
        throw new Exception('No questions found for this quiz');
    }
} catch (Exception $e) {
    header('Location: /codingabcs/client/public/dashboard?error=questions_not_found');
    exit;
}

// 5) Build results array
$results = null;
if ($attempt && count($questions) > 0) {
    // Get the responses from the correct location
    $responses = $attemptData['responses'] ?? [];

    $results = [
        'score'           => $attempt['score'] ?? 0,
        'total_questions' => count($questions),
        'questions'       => array_map(function($q) use ($responses) {
            // Find the saved answer object by question_id
            $selAns = null;
            
            foreach ($responses as $ans) {
                if ($ans['question_id'] == $q['id']) {
                    $selAns = $ans;
                    break;
                }
            }

            if ($q['type'] === 'multiple_choice') {
                // Grab the selected ID
                $selId = $selAns['answer_id'] ?? null;

                // Look up the text of that option
                $selectedText = 'No answer';
                if ($selId !== null) {
                    foreach ($q['answers'] as $opt) {
                        if ($opt['id'] == $selId) {
                            $selectedText = $opt['answer_text'];
                            break;
                        }
                    }
                }

                // find the correct option
                $correctOpt = null;
                foreach ($q['answers'] as $opt) {
                    if (!empty($opt['is_correct'])) {
                        $correctOpt = $opt;
                        break;
                    }
                }
                $correctText = $correctOpt ? $correctOpt['answer_text'] : '';
                $isCorrect = ($selId !== null && $correctOpt && $selId == $correctOpt['id']);

                return [
                    'text'            => $q['question_text'],
                    'selected_answer' => $selectedText,
                    'correct_answer'  => $correctText,
                    'is_correct'      => $isCorrect,
                ];
            }

            // coding question
            $selectedCode = $selAns['submitted_code'] ?? 'No code submitted';
            $output = $selAns['output'] ?? 'No output';
            $isCorrect = !empty($selAns['is_correct']);

            return [
                'text'            => $q['question_text'],
                'selected_answer' => $output,
                'correct_answer'  => $q['expected_output'] ?? 'No expected output provided',
                'is_correct'      => $isCorrect,
                'code'            => $selectedCode
            ];
        }, $questions),
    ];
}
?>
<div class="results-container">
  <?php echo $cardBuilder->addButtonStyles(); ?>
  
  <h1 class="results-title">Quiz Results</h1>

  <?php if (!$results): ?>
    <?php
    echo $cardBuilder->build([
        'title' => 'No Results Found',
        'content' => '<div class="alert alert-warning">
            No results found. Please complete a quiz first.
        </div>',
        'classes' => 'no-results-card'
    ]);
    ?>
  <?php else: ?>
    <?php
    echo $cardBuilder->build([
        'title' => 'Your Score',
        'content' => '<div class="score-value">
            ' . htmlspecialchars($results['score']) . '%
        </div>',
        'classes' => 'score-card'
    ]);
    ?>

    <div class="questions-container">
      <?php foreach ($results['questions'] as $i => $q): ?>
        <?php
        echo $cardBuilder->build([
            'title' => 'Question ' . ($i + 1),
            'content' => '
                <div class="question-header">
                    <span class="status-badge ' . ($q['is_correct'] ? 'badge bg-success' : 'badge bg-danger') . '">
                        ' . ($q['is_correct'] ? 'Correct' : 'Incorrect') . '
                    </span>
                </div>
                <p class="question-text">' . htmlspecialchars($q['text']) . '</p>

                <div class="answers-section">
                    <div class="answer your-answer">
                        <h4>Your Answer:</h4>
                        <p>' . htmlspecialchars($q['selected_answer']) . '</p>
                    </div>
                    ' . (!$q['is_correct'] ? '
                        <div class="answer correct-answer">
                            <h4>Correct Answer:</h4>
                            <p>' . htmlspecialchars($q['correct_answer']) . '</p>
                        </div>
                    ' : '') . '
                </div>
            ',
            'classes' => 'question-card ' . ($q['is_correct'] ? 'correct' : 'incorrect')
        ]);
        ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="results-actions">
    <?php
    echo $cardBuilder->outlineButton('Back to Dashboard', '/codingabcs/client/public/dashboard', 'fas fa-home');
    echo $cardBuilder->primaryButton('Take Another Quiz', '/codingabcs/client/public/quizzes', 'fas fa-redo');
    ?>
  </div>
</div>

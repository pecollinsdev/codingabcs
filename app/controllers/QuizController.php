<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;

/**
 * QuizController handles the quiz functionality.
 *
 * This controller is responsible for displaying the quizzes, starting a quiz,
 * submitting a quiz, and displaying the quiz results. It interacts with the
 * Quiz, UserAttempt, and Question models to fetch the necessary data.
 */
class QuizController extends Controller {
    // QuizModel instance
    private $quizModel;
    
    // UserAttemptModel instance
    private $userAttemptModel;

    // QuestionModel instance
    private $questionModel;

    // Constructor
    public function __construct() {
        Session::start(); // Ensure session is active
        $this->quizModel = $this->model('QuizModel'); // Load QuizModel
        $this->userAttemptModel = $this->model('UserAttemptModel'); // Load UserAttemptModel
        $this->questionModel = $this->model('QuestionModel'); // Load QuestionModel
    }

    // Show all available quizzes
    public function index() {
        $search = $_GET['search'] ?? ''; // Get search query (default: empty)
        $category = $_GET['category'] ?? ''; // Get category (default: empty)
    
        // If "All Categories" is selected (or no category is provided), fetch all quizzes
        if ($category === 'all' || empty($category)) {
            $category = ''; // Pass an empty string to fetch all quizzes
        }
    
        // Fetch quizzes based on search and category
        $quizzes = $this->quizModel->getFilteredQuizzes($search, $category);
        $categories = $this->quizModel->getQuizCategories();
    
        // Load the view with quizzes and category options
        $this->view('quiz/quizzes', [
            'quizzes' => $quizzes,
            'categories' => $categories
        ]);
    }
    
    // Show details of a specific quiz
    public function viewQuiz($quizId) {
        $quiz = $this->quizModel->getQuizById($quizId);
        if (!$quiz) {
            return $this->redirect(BASE_URL . '/quiz'); // Redirect if quiz doesn't exist
        }
        $this->view('quiz/view', ['quiz' => $quiz]);
    }

    // Start a quiz (fetch questions)
    public function start($quizId) {
        $quiz = $this->quizModel->getQuizById($quizId);
        if (!$quiz) {
            return $this->redirect(BASE_URL . '/quiz');
        }

        $questions = $this->questionModel->getQuestionsWithAnswers($quizId);
        if (empty($questions)) {
            return $this->redirect(BASE_URL . "/quiz/view/$quizId?error=no_questions");
        }

        $this->view('quiz/start', [
            'quiz' => $quiz,
            'questions' => $questions
        ]);
    }

    // Handle quiz submission
    public function submit() {
        header('Content-Type: application/json');

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            echo json_encode(["status" => "error", "message" => "Invalid request."]);
            exit;
        }

        $quizId = $_POST['quiz_id'] ?? null;
        $answers = $_POST['answers'] ?? []; // User-selected answers
        $userId = Session::get('user_id');

        if (!$userId) {
            echo json_encode(["status" => "error", "message" => "User not logged in."]);
            exit;
        }

        if (!$quizId || empty($answers)) {
            echo json_encode(["status" => "error", "message" => "Invalid submission."]);
            exit;
        }

        // Fetch correct answers for each question
        $correctAnswers = $this->questionModel->getCorrectAnswers($quizId);
        $score = 0; 
        $totalQuestions = count($correctAnswers);
        $details = [];

        foreach ($correctAnswers as $questionId => $correctAnswerIds) {
            $userSelectedAnswers = (array) ($answers[$questionId] ?? []); 
            $isCorrect = count(array_intersect($userSelectedAnswers, $correctAnswerIds)) > 0 ? 1 : 0; 

            $score += $isCorrect; 

            foreach ($userSelectedAnswers as $selectedAnswerId) {
                $details[] = [
                    'question_id' => $questionId,
                    'user_answer' => $selectedAnswerId,
                    'correct_answers' => implode(", ", $correctAnswerIds),
                    'is_correct' => in_array($selectedAnswerId, $correctAnswerIds) ? 1 : 0
                ];
            }
        }

        // Store user attempt
        $attemptId = $this->userAttemptModel->storeUserAttempt($userId, $quizId, $score);

        // Save user answers properly
        $this->userAttemptModel->saveUserAnswers($attemptId, $answers);

        // Update leaderboard
        $leaderboardModel = $this->model('LeaderboardModel');
        $leaderboardModel->updateLeaderboard($userId, $quizId, $score);

        // Redirect to results page
        echo json_encode(["status" => "success", "redirect" => BASE_URL . "/quiz/result/$quizId/$attemptId"]);
        exit;
    } 

    // Display quiz results
    public function result($quizId, $attemptId) {
        $userId = Session::get('user_id');
    
        $performance = $this->userAttemptModel->getQuizAttemptById($attemptId);
        $questions = $this->userAttemptModel->getQuizAttemptDetails($attemptId);
        $answers = $this->userAttemptModel->getUserAnswersForAttempt($attemptId);
        $quiz = $this->quizModel->getQuizById($quizId);
    
        if (!$performance || !$quiz) {
            die("Invalid attempt or no permission to view.");
        }
        
        /*
        echo "<pre>";
        print_r($performance);
        print_r($questions);
        print_r($quiz);
        echo "</pre>";
        exit;
        */
    
        $this->view('quiz/result', [
            'quiz' => $quiz,
            'performance' => $performance,
            'questions' => $questions,
            'answers' => $answers
        ]);
    }    
}

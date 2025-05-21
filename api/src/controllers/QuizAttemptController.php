<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\QuizAttemptModel;
use App\Models\QuestionResponseModel;
use App\Core\Session;
use App\Models\QuestionModel;
use App\Models\AnswerModel;

/**
 * Controller for managing quiz attempts and responses
 * 
 * Handles submission, retrieval, and scoring of quiz attempts
 */
class QuizAttemptController extends Controller {
    /**
     * @var QuizAttemptModel Quiz attempt model instance
     */
    private QuizAttemptModel $attemptModel;
    
    /**
     * @var QuestionResponseModel Question response model instance
     */
    private QuestionResponseModel $responseModel;

    /**
     * Constructor for the QuizAttemptController
     *
     * @param Request $request The HTTP request object
     */
    public function __construct(Request $request) {
        parent::__construct($request);
        $this->attemptModel = new QuizAttemptModel();
        $this->responseModel = new QuestionResponseModel();
    }

    /**
     * Submit a completed quiz attempt
     * 
     * Endpoint: POST /api/quizzes/{quiz_id}/attempts
     * Access: Authentication required
     * Required fields:
     *   - answers: Array of question responses
     * Optional fields:
     *   - time_taken: Time taken to complete the quiz in seconds
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response with submission results
     */
    public function submitAttempt(Request $request): void {
        $quizId = (int)$request->getParam('quiz_id');
        $userId = $request->getUser()['id'];
        $data = $request->getData();

        // Validate required fields
        if (empty($data['answers']) || !is_array($data['answers'])) {
            $this->respondValidationError(['answers' => 'Answers are required']);
            return;
        }

        // Check for recent duplicate submission
        $recentAttempt = $this->attemptModel->getRecentAttempt($userId, $quizId);
        if ($recentAttempt && !empty($recentAttempt['completed_at'])) {
            $completedAt = new \DateTime($recentAttempt['completed_at']);
            $now = new \DateTime();
            $diff = $now->getTimestamp() - $completedAt->getTimestamp();
            
            if ($diff < 30) {
                $this->respondError([
                    'message' => 'Duplicate submission detected. Please wait a moment before trying again.',
                    'attempt_id' => $recentAttempt['id']
                ], 429);
                return;
            }
        }

        // Get all questions and their correct answers
        $questionModel = new QuestionModel();
        $answerModel = new AnswerModel();
        $questions = $questionModel->getByQuizId($quizId);
        $correctAnswers = [];
        
        foreach ($questions as $q) {
            if ($q['type'] === 'multiple_choice') {
                $answers = $answerModel->getByQuestionId($q['id']);
                foreach ($answers as $a) {
                    if ($a['is_correct']) {
                        $correctAnswers[$q['id']] = $a['id'];
                        break;
                    }
                }
            }
        }

        // Calculate score
        $correctCount = 0;
        foreach ($data['answers'] as $answer) {
            if ($answer['is_correct'] ?? false) {
                $correctCount++;
            } else if (isset($answer['answer_id']) && isset($correctAnswers[$answer['question_id']])) {
                if ($answer['answer_id'] === $correctAnswers[$answer['question_id']]) {
                    $correctCount++;
                }
            }
        }
        // Round the correct count to nearest whole number
        $correctCount = round($correctCount);
        // Calculate percentage and round to nearest whole number
        $score = count($questions) > 0 ? round(($correctCount / count($questions)) * 100) : 0;

        // Save the attempt using saveAttempt method which handles achievements and activities
        try {
            $attemptId = $this->attemptModel->saveAttempt([
                'user_id' => $userId,
                'quiz_id' => $quizId,
                'score' => $score,
                'time_taken' => $data['time_taken'] ?? 0,
                'answers' => $data['answers']
            ]);

            // Clear any saved progress for this quiz
            $key = 'quiz_progress_' . $quizId;
            Session::remove($key);

            $this->respondCreated([
                'message' => 'Quiz attempt submitted successfully',
                'attempt_id' => $attemptId
            ]);
        } catch (\Exception $e) {
            $this->respondError('Failed to save quiz attempt: ' . $e->getMessage());
        }
    }

    /**
     * Get user's attempts for a specific quiz
     * 
     * Endpoint: GET /api/quizzes/{quiz_id}/attempts
     * Access: Authentication required
     * Optional query params:
     *   - user_id: Filter by specific user (admin only)
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response with attempt history
     */
    public function getAttempts(Request $request): void {
        $userId = $request->getParam('user_id');
        $quizId = $request->getParam('quiz_id');
        $currentUserId = $request->getUser()['id'];

        // If user_id is provided, ensure it matches the current user (unless admin)
        if ($userId && $userId != $currentUserId && $request->getUser()['role'] !== 'admin') {
            $this->respondForbidden('Not authorized to view these attempts');
            return;
        }

        $attempts = $this->attemptModel->getAttempts($userId, $quizId);
        $this->respond(['attempts' => $attempts]);
    }

    /**
     * Get a specific quiz attempt with detailed responses
     * 
     * Endpoint: GET /api/quizzes/{quiz_id}/attempts/{attempt_id}
     * Access: Authentication required, user can only view own attempts
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response with attempt details
     */
    public function getAttempt(Request $request): void {
        $quizId = (int)$request->getParam('quiz_id');
        $attemptId = (int)$request->getParam('attempt_id');
        $userId = $request->getUser()['id'];

        // Get the attempt
        $attempt = $this->attemptModel->getById($attemptId);
        if (!$attempt || $attempt['user_id'] !== $userId || $attempt['quiz_id'] !== $quizId) {
            $this->respondNotFound('Quiz attempt not found');
            return;
        }

        // Get the responses
        $responses = $this->responseModel->getByAttemptId($attemptId);
        
        $this->respond([
            'attempt' => $attempt,
            'responses' => $responses
        ]);
    }
}
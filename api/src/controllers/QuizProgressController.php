<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\QuizModel;

/**
 * Controller for managing user's quiz progress
 * 
 * Handles saving, loading, and clearing in-progress quiz attempts
 */
class QuizProgressController extends Controller
{
    /**
     * @var string Namespace prefix for session keys related to quiz progress
     */
    private const NS = 'quiz_progress';

    /**
     * Constructor for the QuizProgressController
     *
     * @param Request $request The HTTP request object
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * Save a user's progress in a quiz
     * 
     * Endpoint: POST /api/quizzes/{quiz_id}/progress
     * Access: Authentication required
     * Required fields:
     *   - current_question: Current question index
     *   - answers: Array of user's answers so far
     *
     * @return void Sends JSON response with saved progress
     */
    public function saveProgress(): void
    {
        $user = $this->request->getUser();
        if (!$user) {
            $this->respondUnauthorized('Authentication required');
            return;
        }

        $quizId = (int) $this->request->getParam('quiz_id');
        if (!$quizId) {
            $this->respondError('Quiz ID is required', 400);
            return;
        }

        $data = $this->request->getData();
        if (!isset($data['current_question'], $data['answers'])) {
            $this->respondValidationError([
                'current_question' => 'Required',
                'answers'          => 'Required'
            ]);
            return;
        }

        $key = self::NS . "_{$quizId}";
        Session::set($key, [
            'current_question' => (int)$data['current_question'],
            'answers'          => $data['answers'],
            'last_updated'     => time(),
        ]);

        $this->respond([
            'current_question' => (int)$data['current_question'],
            'answers'          => $data['answers'],
            'last_updated'     => time(),
        ]);
    }

    /**
     * Load a user's saved progress for a quiz
     * 
     * Endpoint: GET /api/quizzes/{quiz_id}/progress
     * Access: Authentication required
     *
     * @return void Sends JSON response with saved progress or empty state
     */
    public function loadProgress(): void
    {
        $user = $this->request->getUser();
        if (!$user) {
            $this->respondUnauthorized('Authentication required');
            return;
        }

        $quizId = (int) $this->request->getParam('quiz_id');
        if (!$quizId) {
            $this->respondError('Quiz ID is required', 400);
            return;
        }

        $key      = self::NS . "_{$quizId}";
        $progress = Session::get($key) ?: [];

        $this->respond([
            'current_question' => $progress['current_question'] ?? 0,
            'answers'          => $progress['answers']          ?? [],
            'last_updated'     => $progress['last_updated']     ?? null,
        ]);
    }

    /**
     * Clear a user's saved progress for a quiz
     * 
     * Endpoint: DELETE /api/quizzes/{quiz_id}/progress
     * Access: Authentication required
     *
     * @return void Sends JSON response confirming progress was cleared
     */
    public function clearProgress(): void
    {
        $user = $this->request->getUser();
        if (!$user) {
            $this->respondUnauthorized('Authentication required');
            return;
        }

        $quizId = (int) $this->request->getParam('quiz_id');
        if (!$quizId) {
            $this->respondError('Quiz ID is required', 400);
            return;
        }

        $key = self::NS . "_{$quizId}";
        Session::remove($key);

        $this->respond(['message' => 'Progress cleared']);
    }

    /**
     * Get the user's most recently active quiz
     * 
     * Endpoint: GET /api/quizzes/progress
     * Access: Authentication required
     * Returns: Information about the user's most recently accessed
     * in-progress quiz, if one exists
     *
     * @return void Sends JSON response with active quiz data or null
     */
    public function getActiveQuiz(): void
    {
        try {
            $user = $this->request->getUser();
            if (!$user) {
                $this->respondUnauthorized('Authentication required');
                return;
            }

            // Get all session keys that start with quiz_progress_
            $activeQuiz = null;
            $latestTime = 0;

            // Get all session keys
            $sessionKeys = Session::getAll();
            foreach ($sessionKeys as $key => $progress) {
                if (strpos($key, self::NS . '_') === 0) {
                    // Extract quiz ID from the key
                    $quizId = (int)substr($key, strlen(self::NS . '_'));
                    
                    // Skip if no progress or if quiz appears to be completed
                    if (!$progress || !isset($progress['last_updated'])) {
                        continue;
                    }

                    // Check if this quiz has been completed
                    $isCompleted = false;
                    if (isset($progress['answers']) && is_array($progress['answers'])) {
                        foreach ($progress['answers'] as $answer) {
                            if (is_string($answer) && strpos($answer, 'correct)') !== false) {
                                $isCompleted = true;
                                break;
                            }
                        }
                    }
                    
                    if ($isCompleted) {
                        continue;
                    }

                    // Only consider this quiz if it's more recent than our current active quiz
                    if ($progress['last_updated'] > $latestTime) {
                        $latestTime = $progress['last_updated'];
                        $activeQuiz = [
                            'quiz_id' => $quizId,
                            'current_question' => $progress['current_question'] ?? 0,
                            'last_updated' => $progress['last_updated']
                        ];
                    }
                }
            }

            if ($activeQuiz) {
                // Get quiz details only for the active quiz
                $quizModel = new QuizModel();
                $quiz = $quizModel->getById($activeQuiz['quiz_id']);
                
                if ($quiz && !empty($quiz['question_count']) && $quiz['question_count'] > 0) {
                    $activeQuiz['title'] = $quiz['title'] ?? 'Untitled Quiz';
                    $activeQuiz['total_questions'] = (int)$quiz['question_count'];
                    
                    $this->respond([
                        'status' => 'success',
                        'data' => $activeQuiz
                    ]);
                    return;
                }
            }

            $this->respond([
                'status' => 'success',
                'data' => null,
                'message' => 'No active quiz found'
            ]);
        } catch (\Exception $e) {
            $this->respondError('Failed to get active quiz: ' . $e->getMessage());
        }
    }
}

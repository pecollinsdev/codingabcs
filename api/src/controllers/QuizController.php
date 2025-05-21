<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\QuizModel;

/**
 * Controller for managing quizzes
 * 
 * Handles CRUD operations for quizzes and quiz searching
 */
class QuizController extends Controller
{
    /**
     * @var QuizModel Quiz model instance
     */
    private QuizModel $quizModel;

    /**
     * Constructor for the QuizController
     *
     * @param Request $request The HTTP request object
     */
    public function __construct(Request $request)
    {
        parent::__construct($request); // stores it in $this->request
        $this->quizModel = new QuizModel();
    }
    
    /**
     * List quizzes with optional filtering and pagination
     * 
     * Endpoint: GET /api/quizzes
     * Access: Authentication required
     * Optional query params:
     *   - search: Filter by title/description keyword
     *   - difficulty: Filter by difficulty level
     *   - category: Filter by category
     *   - sort: Sort order ('newest', 'oldest', 'title_asc', 'title_desc')
     *   - limit: Number of results per page
     *   - offset: Pagination offset
     *   - all: Include inactive quizzes (admin only)
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response with filtered quizzes
     */
    public function index(Request $request): void
    {
        if (!$request->hasUser()) {
            $this->respondUnauthorized('Authentication required');
            return;
        }

        // Get query parameters
        $search = $_GET['search'] ?? '';
        $difficulty = $_GET['difficulty'] ?? '';
        $category = $_GET['category'] ?? '';
        $sort = $_GET['sort'] ?? 'newest';
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);
        $includeInactive = ($request->getUser()['role'] === 'admin') && ($_GET['all'] ?? false);

        // Get filtered and sorted quizzes
        $quizzes = $this->quizModel->getAll($limit, $offset, $includeInactive, [
            'search' => $search,
            'difficulty' => $difficulty,
            'category' => $category,
            'sort' => $sort
        ]);

        $total = $this->quizModel->count(!$includeInactive, [
            'search' => $search,
            'difficulty' => $difficulty,
            'category' => $category
        ]);

        $this->respond([
            'quizzes' => $quizzes,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'filters' => [
                'search' => $search,
                'difficulty' => $difficulty,
                'category' => $category,
                'sort' => $sort
            ]
        ]);
    }

    /**
     * Get a specific quiz by ID
     * 
     * Endpoint: GET /api/quizzes/{quiz_id}
     * Access: Authentication required
     * Inactive quizzes are only visible to admins
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response with quiz data
     */
    public function show(Request $request): void
    {
        if (!$request->hasUser()) {
            $this->respondUnauthorized('Authentication required');
            return;
        }

        $quizId = (int)$request->getParam('quiz_id');
        if (!$quizId) {
            $this->respondError('Quiz ID is required', 400);
            return;
        }

        $quiz = $this->quizModel->getById($quizId);

        if (!$quiz || (!$quiz['is_active'] && $request->getUser()['role'] !== 'admin')) {
            $this->respondNotFound('Quiz not found');
            return;
        }

        $this->respond($quiz);
    }

    /**
     * Create a new quiz
     * 
     * Endpoint: POST /api/quizzes
     * Access: Admin only
     * Required fields:
     *   - title: Quiz title
     *   - description: Quiz description
     * Optional fields:
     *   - category: Quiz category
     *   - level: Difficulty level (default: 'beginner')
     *   - is_active: Publication status (default: 1)
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response with creation status
     */
    public function store(Request $request): void
    {
        if (!$request->user || $request->user['role'] !== 'admin') {
            $this->respondForbidden('Admin access required');
            return;
        }

        $data = $request->getData();
        if (empty($data['title']) || empty($data['description'])) {
            $this->respondValidationError(['error' => 'Title and description are required']);
            return;
        }

        $quizId = $this->quizModel->create(array_merge($data, [
            'category' => $data['category'] ?? null,
            'level' => $request->getData()['level'] ?? 'beginner',
            'is_active' => $request->getData()['is_active'] ?? 1
        ]));

        if (!$quizId) {
            $this->respondError($this->quizModel->getLastError());
            return;
        }

        $this->respondCreated([
            'message' => 'Quiz created',
            'id' => $quizId
        ]);
    }

    /**
     * Update an existing quiz
     * 
     * Endpoint: PUT /api/quizzes/{quiz_id}
     * Access: Admin only
     * Optional fields (at least one required):
     *   - title: Updated quiz title
     *   - description: Updated quiz description
     *   - category: Updated quiz category
     *   - level: Updated difficulty level
     *   - is_active: Updated publication status
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response with update status
     */
    public function update(Request $request): void
    {
        if (!$request->user || $request->user['role'] !== 'admin') {
            $this->respondForbidden('Admin access required');
            return;
        }

        $quizId = (int)($request->getParam('quiz_id') ?? 0);
        $data = $request->getData();

        if (empty($data)) {
            $this->respondValidationError(['error' => 'No fields provided to update']);
            return;
        }

        $success = $this->quizModel->update($quizId, $data);

        if (!$success) {
            $this->respondError($this->quizModel->getLastError());
            return;
        }

        $this->respond(['message' => 'Quiz updated']);
    }

    /**
     * Soft-delete a quiz
     * 
     * Endpoint: DELETE /api/quizzes/{quiz_id}
     * Access: Admin only
     * 
     * Note: This performs a soft delete, marking the quiz as inactive rather than
     * permanently removing it from the database
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response with deletion status
     */
    public function destroy(Request $request): void
    {
        if (!$request->user || $request->user['role'] !== 'admin') {
            $this->respondForbidden('Admin access required');
            return;
        }

        $quizId = (int)($request->getParam('quiz_id') ?? 0);
        if (!$quizId) {
            $this->respondError('Quiz ID is required', 400);
            return;
        }

        // Log the deletion attempt
        error_log("Attempting to delete quiz ID: " . $quizId);

        try {
            $success = $this->quizModel->delete($quizId);
            
            if (!$success) {
                $errorMessage = $this->quizModel->getLastError();
                error_log("Failed to delete quiz: " . $errorMessage);
                $this->respondError($errorMessage ?: 'Quiz could not be deleted', 400);
                return;
            }

            error_log("Successfully deleted quiz ID: " . $quizId);
            $this->respond(['message' => 'Quiz deleted successfully']);
        } catch (\Exception $e) {
            error_log("Exception while deleting quiz: " . $e->getMessage());
            $this->respondError('An error occurred while deleting the quiz: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Search quizzes by title or keywords
     * 
     * Endpoint: GET /api/quizzes/search
     * Access: Authentication required
     * Required query params:
     *   - q: Search query (minimum 2 characters)
     * Optional query params:
     *   - limit: Number of results per page
     *   - offset: Pagination offset
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response with search results
     */
    public function search(Request $request): void
    {
        if (!$request->user) {
            $this->respondUnauthorized('Authentication required');
            return;
        }

        $q = $_GET['q'] ?? '';
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);

        if (strlen($q) < 2) {
            $this->respondValidationError(['q' => 'Search query must be at least 2 characters']);
            return;
        }

        $results = $this->quizModel->search($q, $limit, $offset);
        $this->respond(['results' => $results]);
    }
}

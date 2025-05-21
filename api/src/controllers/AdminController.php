<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\UserModel;
use App\Models\QuizModel;
use App\Core\Database;

/**
 * Controller for administrative functions
 * 
 * Handles user management, quiz administration, and system statistics
 */
class AdminController extends Controller
{
    /**
     * @var UserModel User model instance
     */
    private UserModel $userModel;
    
    /**
     * @var QuizModel Quiz model instance
     */
    private QuizModel $quizModel;
    
    /**
     * @var Database Database connection instance
     */
    private Database $db;

    /**
     * Constructor for the AdminController
     *
     * @param Request $request The HTTP request object
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->userModel = new UserModel();
        $this->quizModel = new QuizModel();
        $this->db = Database::getInstance();
    }

    /**
     * Check if the current user has admin privileges
     *
     * @return bool True if user is an admin, false otherwise
     */
    private function isAdmin(): bool
    {
        return $this->request->getUser()['role'] === 'admin';
    }

    /**
     * Count the number of admin users in the system
     *
     * @return int Number of admin users
     */
    private function countAdmins(): int
    {
        return $this->userModel->count(['role' => 'admin']);
    }

    /**
     * Admin panel dashboard (User and Quiz Management)
     * 
     * Endpoint: GET /admin/panel
     * Access: Admin only
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response
     */
    public function panel(Request $request): void
    {
        if ($request->getUser()['role'] !== 'admin') {
            $this->respondForbidden('Admin access required');
            return;
        }

        // Retrieve all users and quizzes for display
        $users = $this->userModel->all();
        $quizzes = $this->quizModel->getAll();

        // Render the admin panel with data
        $this->respond(['users' => $users, 'quizzes' => $quizzes]);
    }

    /**
     * Search and manage users
     * 
     * Endpoint: GET /admin/users
     * Access: Admin only
     * Optional query params:
     *   - search: Filter users by keyword
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response
     */
    public function users(Request $request): void
    {
        if ($request->getUser()['role'] !== 'admin') {
            $this->respondForbidden('Admin access required');
            return;
        }

        $search = $request->getParam('search') ?? '';
        $users = $this->userModel->search($search);

        $this->respond(['users' => $users ?? []]);
    }

    /**
     * Get a single user by ID
     * 
     * Endpoint: GET /admin/users/{user_id}
     * Access: Admin only
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response
     */
    public function show(Request $request): void
    {
        if ($request->getUser()['role'] !== 'admin') {
            $this->respondForbidden('Admin access required');
            return;
        }

        $userId = (int)($request->getParam('user_id') ?? 0);
        if (!$userId) {
            $this->respondError('User ID is required');
            return;
        }

        $user = $this->userModel->findById($userId);
        if (!$user) {
            $this->respondNotFound('User not found');
            return;
        }

        // Remove sensitive data
        unset($user['password']);

        $this->respond($user);
    }

    /**
     * Update user details
     * 
     * Endpoint: PATCH /admin/users/{user_id}
     * Access: Admin only
     * Required fields:
     *   - username: User's username
     *   - email: User's email
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response
     */
    public function toggleUserStatus(Request $request): void
    {
        if ($request->getUser()['role'] !== 'admin') {
            $this->respondForbidden('Admin access required');
            return;
        }

        $userId = (int)($request->getParam('user_id') ?? 0);
        if (!$userId) {
            $this->respondError('User ID required');
            return;
        }

        $data = $request->getData();
        
        // Validate required fields
        if (empty($data['username']) || empty($data['email'])) {
            $this->respondValidationError(['error' => 'Username and email are required']);
            return;
        }

        // Check if email already exists for another user
        $existingUser = $this->userModel->findByEmail($data['email']);
        if ($existingUser && $existingUser['id'] != $userId) {
            $this->respondValidationError(['email' => 'Email already in use']);
            return;
        }

        // Check if username already exists for another user
        $existingUser = $this->userModel->findByUsername($data['username']);
        if ($existingUser && $existingUser['id'] != $userId) {
            $this->respondValidationError(['username' => 'Username already in use']);
            return;
        }

        // Update user
        $success = $this->userModel->update($userId, [
            'username' => $data['username'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => $data['is_active']
        ]);

        if (!$success) {
            $this->respondError('Failed to update user');
            return;
        }

        $this->respond(['message' => 'User updated successfully']);
    }

    /**
     * Admin can search quizzes
     * 
     * Endpoint: GET /admin/quizzes
     * Access: Admin only
     * Optional query params:
     *   - search: Filter quizzes by keyword
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response
     */
    public function quizzes(Request $request): void
    {
        if ($request->getUser()['role'] !== 'admin') {
            $this->respondForbidden('Admin access required');
            return;
        }

        $search = $request->getParam('search') ?? '';
        $quizzes = $this->quizModel->getAll(100, 0, true, [
            'search' => $search
        ]);

        $this->respond(['quizzes' => $quizzes ?? []]);
    }

    /**
     * Get questions for a specific quiz
     * 
     * Endpoint: GET /admin/quizzes/{quiz_id}/questions
     * Access: Admin only
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response
     */
    public function quizQuestions(Request $request): void
    {
        if ($request->getUser()['role'] !== 'admin') {
            $this->respondForbidden('Admin access required');
            return;
        }

        $quizId = (int)($request->getParam('quiz_id') ?? 0);
        if (!$quizId) {
            $this->respondError('Quiz ID is required');
            return;
        }

        $questionModel = new \App\Models\QuestionModel();
        $questions = $questionModel->getByQuizId($quizId);

        $this->respond(['questions' => $questions]);
    }

    /**
     * Get admin dashboard statistics
     * 
     * Endpoint: GET /admin/stats
     * Access: Admin only
     * Returns: User and quiz statistics
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response
     */
    public function stats(Request $request): void
    {
        if ($request->getUser()['role'] !== 'admin') {
            $this->respondForbidden('Admin access required');
            return;
        }

        // Get total users
        $totalUsers = $this->userModel->count();
        
        // Get active users
        $activeUsers = $this->userModel->count(['is_active' => 1]);
        
        // Get total quizzes
        $totalQuizzes = $this->quizModel->count();

        $this->respond([
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'totalQuizzes' => $totalQuizzes
        ]);
    }

    /**
     * Create a new user
     * 
     * Endpoint: POST /admin/users
     * Access: Admin only
     * Required fields:
     *   - username: User's username
     *   - email: User's email
     *   - password: User's password
     * Optional fields:
     *   - role: User role (default: 'user')
     *   - is_active: User status (default: 1)
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response
     */
    public function store(Request $request): void
    {
        if ($request->getUser()['role'] !== 'admin') {
            $this->respondForbidden('Admin access required');
            return;
        }

        $data = $request->getData();
        $errors = [];
        
        // Validate required fields
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        }
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        }
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        }

        // If there are validation errors, return them
        if (!empty($errors)) {
            $this->respondValidationError($errors);
            return;
        }

        // Check if email already exists
        if ($this->userModel->findByEmail($data['email'])) {
            $errors['email'] = 'Email already in use';
        }

        // Check if username already exists
        if ($this->userModel->findByUsername($data['username'])) {
            $errors['username'] = 'Username already in use';
        }

        // If there are validation errors, return them
        if (!empty($errors)) {
            $this->respondValidationError($errors);
            return;
        }

        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Set default role if not provided
        $data['role'] = $data['role'] ?? 'user';
        $data['is_active'] = $data['is_active'] ?? 1;

        // Create user
        $userId = $this->userModel->create($data);

        if (!$userId) {
            $this->respondError('Failed to create user');
            return;
        }

        $this->respondCreated([
            'status' => 'success',
            'message' => 'User created successfully',
            'data' => [
                'id' => $userId
            ]
        ]);
    }

    /**
     * Delete a user and all associated records
     * 
     * Endpoint: DELETE /admin/users/{user_id}
     * Access: Admin only
     * Restrictions: Cannot delete the last admin user
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response
     */
    public function destroy(Request $request): void
    {
        // Role check
        if (!$this->isAdmin()) {
            $this->respondError('Unauthorized', 401);
            return;
        }

        // Get user ID from URL parameter
        $userId = (int)$request->getParam('user_id');
        if (!$userId) {
            $this->respondError('User ID is required', 400);
            return;
        }

        try {
            // Start transaction
            $this->db->beginTransaction();

            // First, check if user exists
            $user = $this->userModel->findById($userId);
            if (!$user) {
                $this->respondError('User not found', 404);
                return;
            }

            // Check if this is the last admin user
            if ($user['role'] === 'admin') {
                $adminCount = $this->countAdmins();
                if ($adminCount <= 1) {
                    $this->respondError('Cannot delete the last admin user', 400);
                    return;
                }
            }

            // Delete user activities first
            $this->db->query("DELETE FROM user_activities WHERE user_id = :user_id", ['user_id' => $userId]);
            
            // Delete user achievements
            $this->db->query("DELETE FROM user_achievements WHERE user_id = :user_id", ['user_id' => $userId]);
            
            // Delete quiz attempts
            $this->db->query("DELETE FROM quiz_attempts WHERE user_id = :user_id", ['user_id' => $userId]);

            // Now delete the user
            if ($this->userModel->delete($userId)) {
                $this->db->commit();
                $this->respond([
                    'status' => 'success',
                    'message' => 'User deleted successfully'
                ]);
            } else {
                $this->db->rollBack();
                $this->respondError('Failed to delete user', 500);
            }
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->respondError($e->getMessage(), 500);
        }
    }
}

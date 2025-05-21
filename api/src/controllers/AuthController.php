<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\UserModel;
use App\Services\JwtService;

/**
 * Controller for authentication-related functionality
 * 
 * Handles user registration, login, logout, and token validation
 */
class AuthController extends Controller
{
    /**
     * Constructor for the AuthController
     */
    public function __construct()
    {
    }

    /**
     * Handle user registration
     * 
     * Endpoint: POST /auth/register
     * Required fields:
     *   - email: Valid email address
     *   - password: Password meeting security requirements
     *   - username: Unique username
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response
     */
    public function register(Request $request): void
    {
        $data = $this->validated($request, ['email', 'password', 'username']);
        if ($data === false) return;

        $userModel = new UserModel();

        if ($userModel->findByEmail($data['email'])) {
            $this->respondValidationError(['email' => 'Email already in use']);
            return;
        }

        if ($userModel->findByUsername($data['username'])) {
            $this->respondValidationError(['username' => 'Username already in use']);
            return;
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        $userId = $userModel->create([
            'username'  => $data['username'],
            'email'    => $data['email'],
            'password' => $hashedPassword,
            'role'     => 'user',
            'is_active' => 1
        ]);

        if (!$userId) {
            $this->respondError('User registration failed');
            return;
        }

        // Generate JWT token for the new user
        $token = JwtService::generate([
            'id' => $userId,
            'email' => $data['email'],
            'role' => 'user'
        ]);

        $this->respondCreated([
            'token' => $token,
            'user' => [
                'id' => $userId,
                'email' => $data['email'],
                'role' => 'user'
            ]
        ]);
    }

    /**
     * Handle user login and return JWT token
     * 
     * Endpoint: POST /auth/login
     * Required fields:
     *   - email: User's email address
     *   - password: User's password
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response with JWT token
     */
    public function login(Request $request): void
    {
        $data = $this->validated($request, ['email', 'password']);
        if ($data === false) return;

        $userModel = new UserModel();
        $user = $userModel->findByEmail($data['email']);

        if (!$user || !password_verify($data['password'], $user['password'])) {
            $this->respondUnauthorized('Invalid email or password');
            return;
        }

        if (!$user['is_active']) {
            $this->respondForbidden('Account is deactivated');
            return;
        }

        $userModel->updateLastLogin($user['id']);

        $token = JwtService::generate([
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ]);

        $this->respond([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    }

    /**
     * Handle user logout
     * 
     * Endpoint: POST /auth/logout
     * Clears the JWT token cookie
     *
     * @return void Sends JSON response
     */
    public function logout(): void
    {
        // Clear the JWT token cookie
        setcookie('jwt_token', '', [
            'expires' => time() - 3600,
            'path' => '/codingabcs',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        $this->respond([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Validate required input fields and perform basic validation
     *
     * @param Request $request The HTTP request object
     * @param array $requiredFields List of required field names
     * @return array|false Returns validated data or false if validation fails
     */
    private function validated(Request $request, array $requiredFields): array|false
    {
        $data = $request->getData() ?? [];
        $errors = [];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }

        // Validate email format if email field is present
        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please enter a valid email address';
            } else {
                // Check email format more strictly
                $emailParts = explode('@', $data['email']);
                if (count($emailParts) !== 2 || strlen($emailParts[0]) < 2) {
                    $errors['email'] = 'Please enter a valid email address';
                }
            }
        }

        // Validate password strength if password field is present
        if (isset($data['password'])) {
            $password = $data['password'];
            if (strlen($password) < 8) {
                $errors['password'] = 'Password must be at least 8 characters long';
            } elseif (!preg_match('/[A-Z]/', $password)) {
                $errors['password'] = 'Password must contain at least one uppercase letter';
            } elseif (!preg_match('/[a-z]/', $password)) {
                $errors['password'] = 'Password must contain at least one lowercase letter';
            } elseif (!preg_match('/[0-9]/', $password)) {
                $errors['password'] = 'Password must contain at least one number';
            } elseif (!preg_match('/[@$!%*?&]/', $password)) {
                $errors['password'] = 'Password must contain at least one special character (@$!%*?&)';
            }
        }

        if (!empty($errors)) {
            $this->respondValidationError($errors);
            return false;
        }

        return array_intersect_key($data, array_flip($requiredFields));
    }

    /**
     * Get current authenticated user information
     * 
     * Endpoint: GET /auth/me
     * Requires valid JWT token in request
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response with user information
     */
    public function me(Request $request): void
    {
        if (!$request->user) {
            $this->respondUnauthorized('Not authenticated');
            return;
        }

        $this->respond([
            'message' => 'User information',
            'user' => $request->user
        ]);
    }

    /**
     * Validate JWT token and return API configuration
     * 
     * Endpoint: POST /auth/validate
     * Required fields:
     *   - token: JWT token to validate
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response with user and config information
     */
    public function validate(Request $request): void
    {
        $data = $request->getData();
        $token = $data['token'] ?? null;

        if (!$token) {
            $this->respondValidationError(['token' => 'Token is required']);
            return;
        }

        try {
            $user = JwtService::verify($token);
            
            // Create a direct response without wrapping in status/data
            $response = new Response([
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ],
                'config' => [
                    'auth' => [
                        'enabled' => true,
                        'type' => 'jwt'
                    ],
                    'api' => [
                        'version' => '1.0.0',
                        'base_url' => '/codingabcs/api/public'
                    ]
                ]
            ]);
            $response->send();
        } catch (\Throwable $e) {
            $this->respondUnauthorized('Invalid or expired token');
        }
    }
}

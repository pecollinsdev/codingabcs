<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;

/**
 * AuthController handles user authentication and authorization.
 *
 * This controller is responsible for managing user login, registration,
 * and logout functionalities. It interacts with the User model to
 * perform these operations.
 */
class AuthController extends Controller {
    // User model instance
    private $userModel;
    
    // Constructor
    public function __construct() {
        // Load the User model
        $this->userModel = $this->model('UserModel');
    }

    // Show the registration form
    public function register() {
        // If user is already logged in, redirect to dashboard
        if (Session::has('user_id')) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        // Show the registration view
        $this->view('auth/register');
    }

    // Handle the registration form submission
    public function registerPost() {
        header('Content-Type: application/json'); // Ensure JSON response

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            echo json_encode(["status" => "error", "message" => "Invalid request method."]);
            exit;
        }

        $username = trim($_POST['Username'] ?? '');
        $email = trim($_POST['Email'] ?? '');
        $password = $_POST['Password'] ?? '';
        $rePassword = $_POST['RePassword'] ?? ''; 

        $errors = [];

        // Validate Username
        if (empty($username)) {
            $errors['Username'] = "Username is required.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $errors['Username'] = "Username must be 3-20 characters, letters, numbers, and underscores only.";
        } elseif ($this->userModel->usernameExists($username)) {
            $errors['Username'] = "Username is already in use.";
        }

        // Validate Email
        if (empty($email)) {
            $errors['Email'] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['Email'] = "Invalid email format.";
        } elseif ($this->userModel->Emailexists($email)) {
            $errors['Email'] = "Email is already in use.";
        }

        // Validate Password
        if (empty($password)) {
            $errors['Password'] = "Password is required.";
        } elseif (strlen($password) < 6) {
            $errors['Password'] = "Password must be at least 6 characters.";
        }

        // Validate Password Confirmation
        if (empty($rePassword)) {
            $errors['RePassword'] = "Confirm Password is required.";
        } elseif ($password !== $rePassword) {
            $errors['RePassword'] = "Passwords do not match.";
        }

        // Return validation errors in JSON format
        if (!empty($errors)) {
            echo json_encode(["status" => "error", "errors" => $errors]);
            exit;
        }

        // If validation passes, register the user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $userRegistered = $this->userModel->register($username, $email, $hashedPassword);

        if (!$userRegistered) {
            echo json_encode(["status" => "error", "message" => "Failed to create account. Please try again."]);
            exit;
        }

        // Set session after successful registration
        Session::set('user_id', $this->userModel->getLastInsertedId());
        Session::set('username', $username);
        Session::regenerate();

        echo json_encode(["status" => "success", "message" => "Registration successful!"]);
        exit;
    }

    // Show the login form
    public function login() {
        if (Session::has('user_id')) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        // Show the login view
        $this->view('auth/login');
    }

    // Handle the login form submission
    public function loginPost() {
        header('Content-Type: application/json'); 
    
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            echo json_encode(["status" => "error", "message" => "Invalid request method."]);
            exit;
        }

        // Sanitize Inputs
        $email = filter_var(trim($_POST['Email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['Password'] ?? '';

        $errors = [];

        // Validate Email
        if (empty($email)) {
            $errors['Email'] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['Email'] = "Invalid email format.";
        }

        // Validate Password
        if (empty($password)) {
            $errors['Password'] = "Password is required.";
        }

        if (!empty($errors)) {
            echo json_encode(["status" => "error", "errors" => $errors]);
            exit;
        }

        // Find user securely using prepared statements
        $user = $this->userModel->findByEmail($email);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Secure session
            Session::set('user_id', $user['id']);
            Session::set('username', htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'));
            Session::set('is_admin', (int) $user['is_admin']); 
            
            Session::regenerate(); 

            echo json_encode(["status" => "success", "message" => "Login successful!"]);
            exit;
        } else {
            echo json_encode(["status" => "error", "errors" => ["Password" => "Invalid email or password."]]);
            exit;
        }
    }

    // Handle user logout
    public function logout() {
        // Destroy the session
        Session::destroy();

        // Redirect to the home page
        header('Location: ' . BASE_URL . '/home');
        exit;
    }
}
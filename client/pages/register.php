<?php
require_once __DIR__ . '/../assets/php/form_builder.php';

$formBuilder = new FormBuilder();

// Build registration form
$registerForm = $formBuilder->buildCentered([
    'title' => 'Create Account',
    'icon' => '<i class="fa-solid fa-user-pen fa-4x mb-4" style="color: var(--primary-color);"></i>',
    'action' => '/codingabcs/api/public/index.php/register',
    'method' => 'POST',
    'formId' => 'registerForm',
    'formAttributes' => 'data-ajax="true" novalidate',
    'fields' => [
        [
            'type' => 'text',
            'name' => 'username',
            'id' => 'username',
            'label' => 'Username',
            'placeholder' => 'Choose a username',
            'required' => true,
            'classes' => 'form-control'
        ],
        [
            'type' => 'email',
            'name' => 'email',
            'id' => 'email',
            'label' => 'Email Address',
            'placeholder' => 'Enter your email',
            'required' => true,
            'classes' => 'form-control'
        ],
        [
            'type' => 'password',
            'name' => 'password',
            'id' => 'password',
            'label' => 'Password',
            'placeholder' => 'Create a password',
            'required' => true,
            'classes' => 'form-control'
        ],
        [
            'type' => 'password',
            'name' => 'confirmPassword',
            'id' => 'confirmPassword',
            'label' => 'Confirm Password',
            'placeholder' => 'Confirm your password',
            'required' => true,
            'classes' => 'form-control'
        ]
    ],
    'submitText' => 'Register',
    'submitIcon' => 'fa-solid fa-user-plus',
    'formClasses' => 'register-form',
    'submitClasses' => 'btn-register',
    'classes' => 'p-4',
    'footer' => '<div class="text-center mt-3" style="color: var(--text-color)">Already have an account? <a href="login.php" class="text-primary">Login Here</a></div>'
]);
?>

<main class="auth-container">
    <div class="auth-form-wrapper">
        <?php echo $registerForm; ?>
    </div>
</main>

<script>
    document.body.classList.add('auth-page');
</script>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

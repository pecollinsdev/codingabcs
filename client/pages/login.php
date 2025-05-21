<?php
require_once __DIR__ . '/../assets/php/form_builder.php';

$formBuilder = new FormBuilder();

// Build login form
$loginForm = $formBuilder->buildCentered([
    'title' => 'Welcome Back',
    'icon' => '<i class="fa-solid fa-user fa-4x mb-4" style="color: var(--primary-color);"></i>',
    'action' => '/codingabcs/api/public/login',
    'method' => 'POST',
    'formId' => 'loginForm',
    'formAttributes' => 'data-ajax="true" novalidate',
    'fields' => [
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
            'placeholder' => 'Enter your password',
            'required' => true,
            'classes' => 'form-control'
        ]
    ],
    'submitText' => 'Login',
    'submitIcon' => 'fa-solid fa-right-to-bracket',
    'formClasses' => 'login-form needs-validation',
    'submitClasses' => 'btn-login',
    'classes' => 'p-4',
    'footer' => '<div class="text-center mt-3">New to Coding ABCs? <a href="register.php" class="text-primary">Register Now</a></div>'
]);
?>

<main class="auth-container">
    <div class="auth-form-wrapper">
        <?php echo $loginForm; ?>
    </div>
</main>

<script>
    document.body.classList.add('auth-page');
</script>

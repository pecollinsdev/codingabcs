<?php
require_once '../app/views/layouts/header.php';


use App\Core\Form\FormBuilder;

// Create FormBuilder instance with Bootstrap styling
$form = new FormBuilder(BASE_URL . "/auth/register", "post", true, $errors ?? [], $_POST);

$form->addField("Username", "text", "Username", ["validateRequired", "validateUsername"])
     ->addField("Email", "email", "Email", ["validateRequired", "validateEmail"])
     ->addField("Password", "password", "Password", ["validateRequired", "validatePassword"])
     ->addField("RePassword", "password", "Confirm Password", ["validateConfirmPassword"]);

?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">Create an Account</h4>
                </div>
                <div class="card-body">
                    <?= $form->buildForm(); ?>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Already have an account? <a href="<?= BASE_URL ?>/auth/login" class="text-primary">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="customPopup" class="popup">
    <div class="popup-content">
        <p id="popupMessage"></p>
        <button id="popupCloseBtn">Close</button>
    </div>
</div>

<?php require_once '../app/views/layouts/footer.php'; ?>

<?php 
require_once '../app/views/layouts/header.php'; 
require_once '../app/core/form/FormBuilder.php';

use App\Core\Form\FormBuilder;

// Create FormBuilder instance with Bootstrap styling
$form = new FormBuilder(BASE_URL . "/auth/login", "post", true, $errors ?? [], $_POST);

$form->addField("Email", "email", "Email", ["validateRequired", "validateEmail"])
     ->addField("Password", "password", "Password", ["validateRequired"]);

?>
<div class="container">
  <div class="row justify-content-center mt-5">
      <div class="col-md-6">
          <div class="card shadow-lg border-0">
              <div class="card-header bg-success text-white text-center">
                  <h4 class="mb-0">Login to Your Account</h4>
              </div>
              <div class="card-body">
                  <?= $form->buildForm(); ?>
              </div>
              <div class="card-footer text-center">
                  <p class="mb-0">Don't have an account? <a href="<?= BASE_URL ?>/auth/register" class="text-primary">Register here</a></p>
              </div>
          </div>
      </div>
  </div>
</div>

<?php require_once '../app/views/layouts/validation.php'; ?>

<?php require_once '../app/views/layouts/footer.php'; ?>

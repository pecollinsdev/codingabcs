<?php
$showFooter = true;
require_once '../assets/php/card_builder.php';

$card = new CardBuilder();
$errorContent = '
    <div class="mb-4">
        <i class="fas fa-exclamation-triangle display-1 text-primary mb-3"></i>
        <h1 class="display-1 fw-bold text-primary mb-3">404</h1>
    </div>
    <h2 class="h3 mb-4" style="color: var(--hero-text)">Page Not Found</h2>
    <p class="mb-5 fs-5" style="color: var(--hero-text)">
        Oops! The page you\'re looking for doesn\'t exist or has been moved.
    </p>
    <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
        ' . $card->primaryButton('Go to Homepage', 'home.php', 'fas fa-home') . '
        ' . $card->outlineButton('Go Back', '#', 'fas fa-arrow-left', 'js-go-back') . '
    </div>
';

$errorCard = $card->buildCentered([
    'content' => $errorContent,
    'classes' => 'p-5'
]);
?>

<main class="container d-flex align-items-center" style="min-height: calc(100vh - 56px);">
    <div class="row justify-content-center mx-0 w-100">
        <div class="col-12 col-md-8 col-lg-6 px-0">
            <?php echo $errorCard; ?>
        </div>
    </div>
</main>

<?php echo $card->addButtonStyles(); ?>

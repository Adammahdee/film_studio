<?php
// templates/errors/404.php
// This file is included by ErrorHandler, so ROOT_PATH and url() are available.
require_once ROOT_PATH . 'templates/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-center" style="min-height: 70vh;">
    <div class="text-center">
        <h1 class="display-1 fw-bold">404</h1>
        <p class="fs-3"> <span class="text-danger">Oops!</span> Page not found.</p>
        <p class="lead">
            <?= htmlspecialchars($message ?? "The page you’re looking for doesn’t exist.") ?>
        </p>
        <a href="<?= url('dashboard') ?>" class="btn btn-primary">Go Home</a>
    </div>
</div>

<?php
require_once ROOT_PATH . 'templates/includes/footer.php';
?>
<?php
// templates/errors/403.php
// This file is included by ErrorHandler, so ROOT_PATH and url() are available.
require_once ROOT_PATH . 'templates/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-center" style="min-height: 70vh;">
    <div class="text-center">
        <h1 class="display-1 fw-bold">403</h1>
        <p class="fs-3"> <span class="text-danger">Access Denied!</span></p>
        <p class="lead">
            <?= htmlspecialchars($message ?? "You do not have permission to access this resource.") ?>
        </p>
        <a href="<?= url('dashboard') ?>" class="btn btn-primary">Go to Dashboard</a>
    </div>
</div>

<?php
require_once ROOT_PATH . 'templates/includes/footer.php';
?>
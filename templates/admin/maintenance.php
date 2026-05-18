<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . "templates/includes/settings_loader.php";

$is_maintenance = $settings['maintenance_mode'] ?? false;
$site_name = $settings['site_name'] ?? 'Film Studio';

$role = $_SESSION['role'] ?? '';

/**
 * Redirect out of maintenance page when not needed
 */
if (
    !$is_maintenance ||
    $role === 'ADMIN'
) {
    header("Location: " . url('dashboard'));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - <?= htmlspecialchars($site_name) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f7fb;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .maintenance-card {
            max-width: 500px;
            text-align: center;
        }
    </style>
</head>

<body>

<div class="card shadow-sm maintenance-card p-5">

    <div class="mb-4">
        <h1 class="display-1">🛠️</h1>
    </div>

    <h2 class="mb-3">Under Maintenance</h2>

    <p class="text-muted mb-4">
        <?= htmlspecialchars($site_name) ?> is currently under maintenance.
        Please try again later.
    </p>

    <a href="<?= url('auth') ?>" class="btn btn-outline-primary">
        Admin Login
    </a>

</div>

</body>
</html>
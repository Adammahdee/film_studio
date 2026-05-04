<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Film Studio Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f7fb;
        }

        .landing-shell {
            min-height: 100vh;
        }

        .landing-hero {
            background: linear-gradient(135deg, #172033, #315a7d);
            color: #fff;
        }

        .feature-card {
            border: 0;
            border-radius: 8px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="/film_studio/index.php">Film Studio</a>
        <div class="d-flex gap-2">
            <?php if ($isLoggedIn): ?>
                <a href="/film_studio/dashboard.php" class="btn btn-primary">Dashboard</a>
            <?php else: ?>
                <a href="/film_studio/auth/login.php" class="btn btn-outline-primary">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="landing-shell">
    <section class="landing-hero py-5">
        <div class="container py-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <h1 class="display-5 fw-semibold mb-3">Film Studio Inventory System</h1>
                    <p class="lead mb-4">
                        Manage studio equipment, inventory requests, suppliers, and purchase orders from one organized workspace.
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if ($isLoggedIn): ?>
                            <a href="/film_studio/dashboard.php" class="btn btn-light btn-lg">Open Dashboard</a>
                        <?php else: ?>
                            <a href="/film_studio/auth/login.php" class="btn btn-light btn-lg">Login</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="bg-white text-dark rounded-3 shadow p-4">
                        <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
                            <span class="text-muted">Inventory Status</span>
                            <span class="badge bg-success">Live</span>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span>Equipment tracking</span>
                            <strong>Ready</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span>Request approvals</span>
                            <strong>Managed</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span>Purchase orders</span>
                            <strong>Organized</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title">Inventory Control</h5>
                            <p class="card-text text-muted">Track item quantities, availability, and stock status.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title">Request Workflow</h5>
                            <p class="card-text text-muted">Let staff request equipment and managers approve usage.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title">Procurement</h5>
                            <p class="card-text text-muted">Manage suppliers, purchase orders, and received stock.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

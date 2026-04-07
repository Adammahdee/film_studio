<?php
require_once __DIR__ . "/includes/auth_check.php";
require_once __DIR__ . "/includes/header.php";
?>

<h2 class="mb-4">Dashboard</h2>

<div class="row g-4">
    <?php if ($_SESSION['role'] == 'ADMIN' || $_SESSION['role'] == 'MANAGER'): ?>
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title">Inventory</h5>
                    <p class="card-text">Manage items, quantities, and status.</p>
                    <a href="/film_studio/inventory/index.php" class="btn btn-primary">Manage Inventory</a>
                    <a href="/film_studio/reports/index.php" class="btn btn-info mt-2">View Reports</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title">Suppliers</h5>
                    <p class="card-text">Manage external suppliers and contacts.</p>
                    <a href="/film_studio/suppliers/index.php" class="btn btn-secondary">Manage Suppliers</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title">Treasurer / Logistics / Inventory</h5>
                    <p class="card-text">Create purchase orders and record purchases for stock replenishment.</p>
                    <a href="/film_studio/purchase_orders/index.php" class="btn btn-primary">Manage Purchase Orders</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title">Requests</h5>
                    <p class="card-text">Review and approve user requests.</p>
                    <a href="/film_studio/requests/approve.php" class="btn btn-primary">Approve Requests</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($_SESSION['role'] == 'STAFF'): ?>
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title">Member / Requester</h5>
                    <p class="card-text">Create requests for equipment or inventory items.</p>
                    <a href="/film_studio/requests/create.php" class="btn btn-success">Create Request</a>
                    <a href="/film_studio/requests/my_requests.php" class="btn btn-outline-primary mt-2">My Requests</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

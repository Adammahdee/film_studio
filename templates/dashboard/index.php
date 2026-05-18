<?php
/**
 * Centralized Dashboard Controller View
 * Location: templates/dashboard/index.php
 */

use App\Security\Session;

// Ensure secure session handling
Session::start();

// User context
$role = $_SESSION['role'] ?? 'STAFF';
$isAdmin = in_array($role, ['ADMIN', 'MANAGER'], true);

// Default dashboard statistics
$stats = [
    'total_items'      => 0,
    'pending_requests' => 0,
    'low_stock'        => 0
];

// Fetch dashboard statistics safely
if ($isAdmin) {
    try {
        $stats['total_items'] = (int) $pdo
            ->query("SELECT COUNT(*) FROM inventory")
            ->fetchColumn();

        $stats['pending_requests'] = (int) $pdo
            ->query("SELECT COUNT(*) FROM purchase_orders WHERE status = 'PENDING'")
            ->fetchColumn();

        $stats['low_stock'] = (int) $pdo
            ->query("SELECT COUNT(*) FROM inventory WHERE quantity <= 5")
            ->fetchColumn();

    } catch (\Exception $e) {
        error_log('Dashboard Statistics Error: ' . $e->getMessage());
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Studio Overview</h2>
        <p class="text-muted mb-0">
            Welcome back, <?= htmlspecialchars($role) ?>.
        </p>
    </div>

    <?php if ($isAdmin): ?>
        <div class="d-flex gap-2">
            <a href="<?= url('inventory') ?>" class="btn btn-success">
                Add Item
            </a>

            <a href="<?= url('procurement') ?>" class="btn btn-primary">
                New PO
            </a>
        </div>
    <?php endif; ?>
</div>

<?php if ($isAdmin): ?>
<div class="row g-3 mb-4">

    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body">
                <div class="small opacity-75">Inventory Items</div>
                <div class="fs-2 fw-bold">
                    <?= $stats['total_items'] ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-warning text-dark">
            <div class="card-body">
                <div class="small opacity-75">Pending Orders</div>
                <div class="fs-2 fw-bold">
                    <?= $stats['pending_requests'] ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-danger text-white">
            <div class="card-body">
                <div class="small opacity-75">Low Stock Alerts</div>
                <div class="fs-2 fw-bold">
                    <?= $stats['low_stock'] ?>
                </div>
            </div>
        </div>
    </div>

</div>
<?php endif; ?>

<div class="row g-4">

    <div class="col-md-8">
        <div class="card shadow-sm h-100">

            <div class="card-header bg-white py-3">
                <strong>Quick Navigation Modules</strong>
            </div>

            <div class="card-body">

                <div class="list-group list-group-flush">

                    <a href="<?= url('inventory') ?>"
                       class="list-group-item list-group-item-action py-3">
                        📦 Manage Inventory Gear & Assets
                    </a>

                    <a href="<?= url('procurement') ?>"
                       class="list-group-item list-group-item-action py-3">
                        📑 View Procurement Requests & Purchase Orders
                    </a>

                    <a href="<?= url('suppliers') ?>"
                       class="list-group-item list-group-item-action py-3">
                        👥 Supplier Directory
                    </a>

                    <a href="<?= url('reports') ?>"
                       class="list-group-item list-group-item-action py-3">
                        📊 Reports & Analytics
                    </a>

                </div>

            </div>
        </div>
    </div>

    <div class="col-md-4">

        <div class="card shadow-sm">

            <div class="card-header bg-white py-3">
                <strong>Account Information</strong>
            </div>

            <div class="card-body">

                <p>
                    <strong>Session Signature:</strong>
                    <code class="text-dark">
                        <?= htmlspecialchars(substr(session_id(), 0, 8)) ?>...
                    </code>
                </p>

                <p>
                    <strong>Clearance Rank:</strong>
                    <span class="badge bg-dark">
                        <?= htmlspecialchars($role) ?>
                    </span>
                </p>

                <hr>

                <a href="<?= url('auth', 'logout') ?>"
                   class="btn btn-outline-danger w-100">
                    Sign Out Safely
                </a>

            </div>
        </div>

    </div>

</div>

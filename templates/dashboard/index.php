<?php
/**
 * Centralized Dashboard Controller
 */
require_once ROOT_PATH . 'src/Auth/Auth_check.php';
require_once ROOT_PATH . 'templates/includes/header.php';

/* --- Fetch Dashboard Statistics --- */
$role = $_SESSION['role'] ?? '';
$isAdmin = in_array($role, ['ADMIN', 'MANAGER']);

// Initialize stats to prevent undefined variable diagnostics for non-admin users
$stats = [
    'total_items'      => 0,
    'pending_requests' => 0,
    'low_stock'        => 0
];

if ($isAdmin) {
    $stats = [
        'total_items'      => (int) $conn->query("SELECT COUNT(*) FROM inventory")->fetchColumn(),
        'pending_requests' => (int) $conn->query("SELECT COUNT(*) FROM requests WHERE status = 'PENDING'")->fetchColumn(),
        'low_stock'        => (int) $conn->query("SELECT COUNT(*) FROM inventory WHERE quantity <= 5")->fetchColumn()
    ];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Studio Overview</h2>
        <p class="text-muted">Welcome back, <?= htmlspecialchars($_SESSION['role']) ?>.</p>
    </div>
    <?php if ($isAdmin): ?>
        <div class="d-flex gap-2">
            <a href="<?= url('inventory', 'add') ?>" class="btn btn-success">Add Item</a>
            <a href="<?= url('purchase_orders', 'create') ?>" class="btn btn-primary">New PO</a>
        </div>
    <?php endif; ?>
</div>

<?php if ($isAdmin): ?>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-primary text-white">
            <div class="card-body">
                <div class="small opacity-75">Inventory Items</div>
                <div class="fs-2 fw-bold"><?= $stats['total_items'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-warning text-dark">
            <div class="card-body">
                <div class="small opacity-75">Pending Requests</div>
                <div class="fs-2 fw-bold"><?= $stats['pending_requests'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-danger text-white">
            <div class="card-body">
                <div class="small opacity-75">Low Stock Alert</div>
                <div class="fs-2 fw-bold"><?= $stats['low_stock'] ?></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3">Quick Navigation</div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a href="<?= url('inventory') ?>" class="list-group-item list-group-item-action py-3">Manage Inventory Gear</a>
                    <a href="<?= url('requests') ?>" class="list-group-item list-group-item-action py-3">View Procurement Requests</a>
                    <a href="<?= url('suppliers') ?>" class="list-group-item list-group-item-action py-3">Supplier Directory</a>
                    <?php if ($role === 'ADMIN'): ?>
                        <a href="<?= url('settings') ?>" class="list-group-item list-group-item-action py-3 text-primary fw-bold">System Administration</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">Account Information</div>
            <div class="card-body">
                <p><strong>Session ID:</strong> <span class="text-muted"><?= substr(session_id(), 0, 8) ?>...</span></p>
                <p><strong>Authorization:</strong> <span class="badge bg-dark"><?= $role ?></span></p>
                <hr>
                <a href="<?= url('auth', 'logout') ?>" class="btn btn-outline-danger w-100">Sign Out</a>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . 'templates/includes/footer.php'; ?>

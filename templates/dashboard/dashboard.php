<?php
require_once __DIR__ . "/includes/auth_check.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/header.php";

$role = $_SESSION['role'] ?? '';
$isAdmin = $role === 'ADMIN' || $role === 'MANAGER';

if ($isAdmin) {
    $stats = [
        'total_items' => (int) $conn->query("SELECT COUNT(*) FROM inventory")->fetchColumn(),
        'total_stock' => (int) $conn->query("SELECT COALESCE(SUM(quantity), 0) FROM inventory")->fetchColumn(),
        'pending_requests' => (int) $conn->query("SELECT COUNT(*) FROM requests WHERE status = 'PENDING'")->fetchColumn(),
        'pending_orders' => (int) $conn->query("SELECT COUNT(*) FROM purchase_orders WHERE status = 'PENDING'")->fetchColumn(),
        'suppliers' => (int) $conn->query("SELECT COUNT(*) FROM suppliers")->fetchColumn(),
        'out_of_stock' => (int) $conn->query("SELECT COUNT(*) FROM inventory WHERE status = 'OUT_OF_STOCK' OR quantity <= 0")->fetchColumn(),
    ];

    $lowStockStmt = $conn->query("
        SELECT item_id, item_name, quantity, status
        FROM inventory
        WHERE quantity <= 5 OR status = 'OUT_OF_STOCK'
        ORDER BY quantity ASC, item_name ASC
        LIMIT 6
    ");
    $lowStockItems = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);

    $recentRequestsStmt = $conn->query("
        SELECT r.request_id, r.quantity, r.status, u.full_name, i.item_name
        FROM requests r
        JOIN users u ON r.user_id = u.user_id
        JOIN inventory i ON r.item_id = i.item_id
        ORDER BY r.request_id DESC
        LIMIT 5
    ");
    $recentRequests = $recentRequestsStmt->fetchAll(PDO::FETCH_ASSOC);

    $recentOrdersStmt = $conn->query("
        SELECT po.*, s.name AS supplier_name,
               (SELECT COUNT(*) FROM purchase_order_items poi WHERE poi.po_id = po.po_id) AS item_count
        FROM purchase_orders po
        JOIN suppliers s ON po.supplier_id = s.supplier_id
        ORDER BY po.po_id DESC
        LIMIT 5
    ");
    $recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);
}

function dashboardBadgeClass($status)
{
    if ($status === 'PENDING') {
        return 'bg-warning text-dark';
    }
    if ($status === 'APPROVED' || $status === 'RECEIVED' || $status === 'AVAILABLE') {
        return 'bg-success';
    }
    if ($status === 'REJECTED' || $status === 'OUT_OF_STOCK') {
        return 'bg-danger';
    }
    return 'bg-secondary';
}
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Dashboard</h2>
        <p class="text-muted mb-0">Overview of inventory, requests, and purchasing activity.</p>
    </div>

    <?php if ($isAdmin): ?>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= url('inventory', 'add') ?>" class="btn btn-success">Add Item</a>
            <a href="<?= url('purchase_orders', 'create') ?>" class="btn btn-primary">Create Purchase Order</a>
            <a href="<?= url('requests', 'approve') ?>" class="btn btn-outline-primary">Approve Requests</a>
        </div>
    <?php endif; ?>
</div>

<?php if ($isAdmin): ?>
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-2">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Items</div>
                    <div class="fs-3 fw-semibold"><?= $stats['total_items'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Total Stock</div>
                    <div class="fs-3 fw-semibold"><?= $stats['total_stock'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Pending Requests</div>
                    <div class="fs-3 fw-semibold"><?= $stats['pending_requests'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Pending Orders</div>
                    <div class="fs-3 fw-semibold"><?= $stats['pending_orders'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Suppliers</div>
                    <div class="fs-3 fw-semibold"><?= $stats['suppliers'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Out of Stock</div>
                    <div class="fs-3 fw-semibold"><?= $stats['out_of_stock'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Low Stock Items</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$lowStockItems): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No low stock items.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($lowStockItems as $item): ?>
                                <tr>
                                    <td>
                                        <a href="<?= url('inventory', 'edit', ['id' => $item['item_id']]) ?>">
                                            <?= htmlspecialchars($item['item_name']) ?>
                                        </a>
                                    </td>
                                    <td><?= (int) $item['quantity'] ?></td>
                                    <td>
                                        <span class="badge <?= dashboardBadgeClass($item['status']) ?>">
                                            <?= htmlspecialchars($item['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Recent Requests</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Requester</th>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$recentRequests): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No recent requests.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($recentRequests as $request): ?>
                                <tr>
                                    <td><?= htmlspecialchars($request['full_name']) ?></td>
                                    <td><?= htmlspecialchars($request['item_name']) ?></td>
                                    <td><?= (int) $request['quantity'] ?></td>
                                    <td>
                                        <span class="badge <?= dashboardBadgeClass($request['status']) ?>">
                                            <?= htmlspecialchars($request['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Recent Purchase Orders</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>PO</th>
                                <th>Supplier</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$recentOrders): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No purchase orders.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="<?= url('purchase_orders', 'view', ['id' => $order['po_id']]) ?>">
                                            #<?= $order['po_id'] ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($order['supplier_name']) ?></td>
                                    <td><?= (int) $order['item_count'] ?></td>
                                    <td>
                                        <span class="badge <?= dashboardBadgeClass($order['status']) ?>">
                                            <?= htmlspecialchars($order['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($order['created_at'] ?? $order['order_date'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Quick Actions</div>
                <div class="card-body d-grid gap-2">
                    <a href="<?= url('inventory') ?>" class="btn btn-outline-primary text-start">Manage Inventory</a>
                    <a href="<?= url('suppliers') ?>" class="btn btn-outline-secondary text-start">Manage Suppliers</a>
                    <a href="<?= url('purchase_orders') ?>" class="btn btn-outline-primary text-start">Manage Purchase Orders</a>
                    <a href="<?= url('reports') ?>" class="btn btn-outline-info text-start">View Reports</a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($role === 'STAFF'): ?>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Request Inventory</h5>
                    <p class="card-text text-muted">Create requests for equipment or inventory items and track your request history.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= url('requests', 'create') ?>" class="btn btn-success">Create Request</a>
                        <a href="<?= url('requests', 'my') ?>" class="btn btn-outline-primary">My Requests</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

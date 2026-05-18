<?php
use App\Core\ErrorHandler;

// Role-based verification check via routing context session states
$role = $_SESSION['role'] ?? '';
if ($role !== 'ADMIN' && $role !== 'MANAGER') {
    ErrorHandler::render403();
    exit();
}

// FETCH ORDERS - Refactored query to bind precisely with your schema keys
$orders = $pdo->query("
    SELECT po.*, po.purchase_order_id AS po_id, s.name AS supplier_name,
    (SELECT COUNT(*) FROM purchase_order_items poi WHERE poi.purchase_order_id = po.purchase_order_id) AS item_count
    FROM purchase_orders po
    JOIN suppliers s ON po.supplier_id = s.supplier_id
    ORDER BY po.purchase_order_id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Purchase Orders</h2>
    <div class="d-flex gap-2">
        <a href="<?= url('purchase_orders', 'create') ?>" class="btn btn-primary">Create Purchase Order</a>
        <a href="<?= url('purchase_orders', 'create') ?>" class="btn btn-outline-success">Record Purchase</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-striped table-hover table-bordered mb-0">
            <thead class="table-dark">
                <tr>
                    <th>PO ID</th>
                    <th>Supplier</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No purchase orders found.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td>#<?= htmlspecialchars($o['po_id']) ?></td>
                    <td><?= htmlspecialchars($o['supplier_name']) ?></td>
                    <td><?= (int) $o['item_count'] ?></td>
                    <td>
                        <?php 
                        $badge = 'bg-secondary';
                        $statusText = strtoupper($o['status'] ?? 'PENDING');
                        if ($statusText === 'PENDING') $badge = 'bg-warning text-dark';
                        if ($statusText === 'RECEIVED' || $statusText === 'APPROVED') $badge = 'bg-success';
                        if ($statusText === 'REJECTED') $badge = 'bg-danger';
                        ?>
                        <span class="badge <?= $badge ?>"><?= htmlspecialchars($statusText) ?></span>
                    </td>
                    <td><?= htmlspecialchars($o['created_at'] ?? $o['order_date'] ?? 'N/A') ?></td>
                    <td>
                        <a href="<?= url('purchase_orders', 'view', ['id' => $o['po_id']]) ?>" class="btn btn-info btn-sm">View</a>
                        <?php if (($o['status'] ?? 'PENDING') === 'PENDING'): ?>
                            <a href="<?= url('purchase_orders', 'receive', ['id' => $o['po_id']]) ?>" class="btn btn-success btn-sm">Record Purchase</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <a href="<?= url('dashboard') ?>" class="btn btn-secondary">Back</a>
</div>
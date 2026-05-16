<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once ROOT_PATH . 'config/db.php';

if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}

require_once __DIR__ . "/../includes/header.php";

// FETCH ORDERS
$orders = $conn->query("
    SELECT po.*, s.name AS supplier_name,
    (SELECT COUNT(*) FROM purchase_order_items poi WHERE poi.po_id = po.po_id) as item_count
    FROM purchase_orders po
    JOIN suppliers s ON po.supplier_id = s.supplier_id
    ORDER BY po.po_id DESC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Purchase Orders</h2>
    <div class="d-flex gap-2">
        <a href="create.php" class="btn btn-primary">Create Purchase Order</a>
        <a href="create.php" class="btn btn-outline-success">Record Purchase</a>
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
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><?= $o['po_id'] ?></td>
                    <td><?= htmlspecialchars($o['supplier_name']) ?></td>
                    <td><?= $o['item_count'] ?></td>
                    <td>
                        <?php 
                        $badge = 'bg-secondary';
                        if (($o['status'] ?? '') == 'PENDING') $badge = 'bg-warning text-dark';
                        if (($o['status'] ?? '') == 'RECEIVED') $badge = 'bg-success';
                        ?>
                        <span class="badge <?= $badge ?>"><?= $o['status'] ?? 'N/A' ?></span>
                    </td>
                    <td><?= $o['created_at'] ?? $o['order_date'] ?? 'N/A' ?></td>
                    <td>
                        <a href="view.php?id=<?= $o['po_id'] ?>" class="btn btn-info btn-sm">View</a>
                        <?php if (($o['status'] ?? '') == 'PENDING'): ?>
                            <a href="receive.php?id=<?= $o['po_id'] ?>" class="btn btn-success btn-sm">Record Purchase</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <a href="/film_studio/dashboard.php" class="btn btn-secondary">Back</a>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>

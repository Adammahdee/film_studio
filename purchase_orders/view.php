<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}

require_once __DIR__ . "/../includes/header.php";

$id = $_GET['id'] ?? 0;

// Get order
$stmt = $conn->prepare("
    SELECT po.*, s.name AS supplier_name
    FROM purchase_orders po
    JOIN suppliers s ON po.supplier_id = s.supplier_id
    WHERE po.po_id = ?
");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Get order items
$stmt = $conn->prepare("
    SELECT poi.*, i.item_name
    FROM purchase_order_items poi
    JOIN inventory i ON poi.inventory_id = i.item_id
    WHERE poi.po_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Order #<?= htmlspecialchars($id) ?></h2>
    <a href="index.php" class="btn btn-secondary">Back</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <p class="mb-1"><strong>Supplier:</strong> <?= htmlspecialchars($order['supplier_name'] ?? 'N/A') ?></p>
        <p class="mb-0"><strong>Status:</strong> 
            <?php 
                $badge = 'bg-secondary';
                if (($order['status'] ?? '') == 'PENDING') $badge = 'bg-warning text-dark';
                if (($order['status'] ?? '') == 'RECEIVED') $badge = 'bg-success';
            ?>
            <span class="badge <?= $badge ?>"><?= htmlspecialchars($order['status'] ?? 'N/A') ?></span>
        </p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">Order Items</div>
    <div class="card-body p-0">
        <table class="table table-striped table-hover table-bordered mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= $item['unit_price'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
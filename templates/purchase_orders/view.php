<?php

use App\Core\ErrorHandler;
use App\Security\Session;

// Secure session start
Session::start();

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: " . url('auth'));
    exit();
}

// Role access control
if (!in_array($_SESSION['role'] ?? '', ['ADMIN', 'MANAGER'])) {
    ErrorHandler::render403("Access denied.");
    exit();
}

// Validate purchase order ID
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    ErrorHandler::render404("Invalid purchase order ID.");
    exit();
}

// Fetch purchase order details
$stmt = $pdo->prepare("
    SELECT 
        po.purchase_order_id,
        po.status,
        po.total_amount,
        po.order_date,
        s.name AS supplier_name
    FROM purchase_orders po
    JOIN suppliers s 
        ON po.supplier_id = s.supplier_id
    WHERE po.purchase_order_id = ?
");

$stmt->execute([$id]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    ErrorHandler::render404("Purchase order not found.");
    exit();
}

// Fetch purchase order items
// Support both schemas: inventory.name and inventory.item_name
$inventoryNameColumn = 'name';
$invNameStmt = $pdo->query("SHOW COLUMNS FROM inventory LIKE 'name'");
if (!$invNameStmt->fetch(PDO::FETCH_ASSOC)) {
    $inventoryNameColumn = 'item_name';
}

$itemSql = sprintf("
    SELECT 
        poi.quantity,
        poi.unit_price,
        i.%s AS item_name
    FROM purchase_order_items poi
    JOIN inventory i 
        ON poi.item_id = i.item_id
    WHERE poi.purchase_order_id = ?
", $inventoryNameColumn);
$itemStmt = $pdo->prepare($itemSql);

$itemStmt->execute([$id]);

$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container my-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            Purchase Order #<?= htmlspecialchars($order['purchase_order_id']) ?>
        </h2>

        <a href="<?= url('purchase_orders') ?>" class="btn btn-secondary">
            Back
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-6">
                    <strong>Supplier:</strong><br>
                    <?= htmlspecialchars($order['supplier_name']) ?>
                </div>

                <div class="col-md-3">
                    <strong>Status:</strong><br>

                    <?php
                    $badge = 'bg-secondary';

                    if ($order['status'] === 'PENDING') {
                        $badge = 'bg-warning text-dark';
                    } elseif ($order['status'] === 'RECEIVED') {
                        $badge = 'bg-success';
                    }
                    ?>

                    <span class="badge <?= $badge ?>">
                        <?= htmlspecialchars($order['status']) ?>
                    </span>
                </div>

                <div class="col-md-3">
                    <strong>Order Date:</strong><br>
                    <?= htmlspecialchars(date('M d, Y H:i', strtotime($order['order_date']))) ?>
                </div>

                <div class="col-md-12">
                    <strong>Total Amount:</strong><br>
                    $<?= number_format((float)$order['total_amount'], 2) ?>
                </div>

            </div>

        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            Order Items
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-striped table-hover align-middle mb-0">

                    <thead class="table-dark">
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Line Total</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($items)): ?>

                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No order items found.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($items as $item): ?>

                                <tr>
                                    <td>
                                        <?= htmlspecialchars($item['item_name']) ?>
                                    </td>

                                    <td>
                                        <?= (int)$item['quantity'] ?>
                                    </td>

                                    <td>
                                        $<?= number_format((float)$item['unit_price'], 2) ?>
                                    </td>

                                    <td>
                                        $<?= number_format(
                                            $item['quantity'] * $item['unit_price'],
                                            2
                                        ) ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>
    </div>

</div>

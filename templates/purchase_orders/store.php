<?php

use App\Security\Session;

// Ensure secure session is active
Session::start();

// Authentication protection
if (!isset($_SESSION['user_id'])) {
    header("Location: " . url('auth'));
    exit();
}

// Authorization protection
if (!in_array($_SESSION['role'] ?? '', ['ADMIN', 'MANAGER'])) {
    header("Location: " . url('purchase_orders', null, [
        'error' => 'Unauthorized access.'
    ]));
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . url('purchase_orders', 'create'));
    exit();
}

// Validate input
$supplier_id = filter_input(INPUT_POST, 'supplier_id', FILTER_VALIDATE_INT);

$save_mode = strtoupper(trim($_POST['save_mode'] ?? 'PENDING'));
if (!in_array($save_mode, ['PENDING', 'RECEIVED'], true)) {
    header("Location: " . url('purchase_orders', 'create', [
        'error' => 'Invalid purchase order status.'
    ]));
    exit();
}

$inventory_ids = $_POST['inventory_id'] ?? [];
$quantities = $_POST['quantity'] ?? [];
$prices = $_POST['price'] ?? [];

if (
    !$supplier_id ||
    empty($inventory_ids) ||
    count($inventory_ids) !== count($quantities) ||
    count($inventory_ids) !== count($prices)
) {
    header("Location: " . url('purchase_orders', 'create', [
        'error' => 'Invalid purchase order data.'
    ]));
    exit();
}

try {

    $pdo->beginTransaction();

    // Calculate total amount
    $total_amount = 0;

    foreach ($inventory_ids as $index => $inventory_id) {

        $qty = (int) ($quantities[$index] ?? 0);
        $unit_price = (float) ($prices[$index] ?? 0);

        if ($qty <= 0 || $unit_price < 0) {
            throw new Exception('Invalid quantity or price detected.');
        }

        $total_amount += ($qty * $unit_price);
    }

    // Create purchase order
    $purchaseOrderStmt = $pdo->prepare("
        INSERT INTO purchase_orders (
            supplier_id,
            total_amount,
            status,
            created_by,
            order_date
        )
        VALUES (
            :supplier_id,
            :total_amount,
            :status,
            :created_by,
            NOW()
        )
    ");

    $purchaseOrderStmt->execute([
        ':supplier_id' => $supplier_id,
        ':total_amount' => $total_amount,
        ':status' => $save_mode,
        ':created_by' => $_SESSION['user_id']
    ]);

    $purchase_order_id = $pdo->lastInsertId();

    // Prepare statements
    $itemStmt = $pdo->prepare("
        INSERT INTO purchase_order_items (
            purchase_order_id,
            item_id,
            quantity,
            unit_price
        )
        VALUES (
            :purchase_order_id,
            :item_id,
            :quantity,
            :unit_price
        )
    ");

    $stockStmt = $pdo->prepare("
        UPDATE inventory
        SET quantity = quantity + :quantity,
            status = CASE
                WHEN (quantity + :quantity) > 0 THEN 'AVAILABLE'
                ELSE 'OUT_OF_STOCK'
            END
        WHERE item_id = :item_id
    ");

    // Save all PO items
    $insertedItems = 0;
    foreach ($inventory_ids as $index => $inventory_id) {

        $item_id = (int) $inventory_id;
        $qty = (int) ($quantities[$index] ?? 0);
        $unit_price = (float) ($prices[$index] ?? 0);

        if ($item_id <= 0 || $qty <= 0 || $unit_price < 0) {
            throw new Exception('Invalid item data detected.');
        }

        // Insert PO item
        $itemStmt->execute([
            ':purchase_order_id' => $purchase_order_id,
            ':item_id' => $item_id,
            ':quantity' => $qty,
            ':unit_price' => $unit_price
        ]);
        $insertedItems++;

        // Auto update inventory if directly received
        if ($save_mode === 'RECEIVED') {

            $stockStmt->execute([
                ':quantity' => $qty,
                ':item_id' => $item_id
            ]);

            if ($stockStmt->rowCount() === 0) {
                throw new Exception('Inventory item not found for stock update.');
            }
        }
    }

    if ($insertedItems === 0) {
        throw new Exception('Purchase order must contain at least one item.');
    }

    $pdo->commit();

    header("Location: " . url('purchase_orders', null, [
        'success' => 'Purchase order created successfully.'
    ]));
    exit();

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Purchase Order Store Error: " . $e->getMessage());

    header("Location: " . url('purchase_orders', 'create', [
        'error' => 'Failed to create purchase order.'
    ]));
    exit();
}

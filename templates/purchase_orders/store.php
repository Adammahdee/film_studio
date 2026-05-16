<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once ROOT_PATH . 'config/db.php';

if ($_SESSION['role'] !== 'ADMIN' && $_SESSION['role'] !== 'MANAGER') {
    die("Access denied");
}

if (!isset($_POST['supplier_id'])) {
    die("Invalid request");
}

$conn->beginTransaction();

try {
    $supplier_id = (int) $_POST['supplier_id'];
    $user_id = (int) $_SESSION['user_id'];
    $save_mode = $_POST['save_mode'] ?? 'pending';
    $po_status = $save_mode === 'received' ? 'RECEIVED' : 'PENDING';
    $inventory_ids = $_POST['inventory_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['price'] ?? [];

    if ($supplier_id <= 0) {
        throw new Exception("Supplier is required.");
    }

    if (!$inventory_ids || !$quantities || !$prices) {
        throw new Exception("At least one item is required.");
    }

    $stmt = $conn->prepare("
        INSERT INTO purchase_orders (supplier_id, created_by, status)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$supplier_id, $user_id, $po_status]);

    $po_id = $conn->lastInsertId();
    $has_items = false;

    foreach ($inventory_ids as $index => $inv_id) {
        $inv_id = (int) $inv_id;
        $qty = (int) ($quantities[$index] ?? 0);
        $price = (float) ($prices[$index] ?? 0);

        if ($inv_id <= 0 || $qty <= 0) {
            continue;
        }

        if ($price < 0) {
            throw new Exception("Unit price cannot be negative.");
        }

        $has_items = true;

        $stmt = $conn->prepare("
            INSERT INTO purchase_order_items (po_id, inventory_id, quantity, unit_price)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$po_id, $inv_id, $qty, $price]);

        if ($po_status === 'RECEIVED') {
            $updateStmt = $conn->prepare("
                UPDATE inventory
                SET quantity = quantity + ?,
                    status = 'AVAILABLE'
                WHERE item_id = ?
            ");
            $updateStmt->execute([$qty, $inv_id]);
        }
    }

    if (!$has_items) {
        throw new Exception("Add at least one valid purchase item.");
    }

    $conn->commit();
    header("Location: index.php");
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    header("Location: create.php?error=" . urlencode($e->getMessage()));
    exit();
}

<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once ROOT_PATH . 'config/db.php';

// Security Check
if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}

$id = $_GET['id'] ?? 0;

$conn->beginTransaction();

try {
    // Check order status
    $stmt = $conn->prepare("
        SELECT status FROM purchase_orders WHERE po_id = ?
    ");
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order || $order['status'] !== 'PENDING') {
        throw new Exception("Invalid or already received");
    }

    // Get items
    $itemsStmt = $conn->prepare("
        SELECT inventory_id, quantity
        FROM purchase_order_items
        WHERE po_id = ?
    ");
    $itemsStmt->execute([$id]);

    // Update inventory
    while ($item = $itemsStmt->fetch(PDO::FETCH_ASSOC)) {
        $updateStmt = $conn->prepare("
            UPDATE inventory
            SET quantity = quantity + ?,
                status = 'AVAILABLE'
            WHERE item_id = ?
        ");
        $updateStmt->execute([
            $item['quantity'],
            $item['inventory_id']
        ]);
    }

    // Update order status
    $stmt = $conn->prepare("
        UPDATE purchase_orders
        SET status = 'RECEIVED'
        WHERE po_id = ?
    ");
    $stmt->execute([$id]);

    $conn->commit();

    header("Location: index.php");
    exit;

} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}

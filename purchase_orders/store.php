<?php
session_start();
require_once "../config/db.php";

if (!isset($_POST['supplier_id'])) {
    die("Invalid request");
}

$conn->beginTransaction();

try {
    $supplier_id = $_POST['supplier_id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        INSERT INTO purchase_orders (supplier_id, created_by)
        VALUES (?, ?)
    ");
    $stmt->execute([$supplier_id, $user_id]);

    $po_id = $conn->lastInsertId();

    foreach ($_POST['inventory_id'] as $index => $inv_id) {
        $qty = $_POST['quantity'][$index];
        $price = $_POST['price'][$index];

        if ($qty <= 0) continue;

        $stmt = $conn->prepare("
            INSERT INTO purchase_order_items (po_id, inventory_id, quantity, unit_price)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$po_id, $inv_id, $qty, $price]);
    }

    $conn->commit();
    header("Location: index.php");

} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}
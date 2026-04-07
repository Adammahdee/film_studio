<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}

$id = $_GET['id'] ?? 0;

if ($id) {
    try {
        $stmt = $conn->prepare("DELETE FROM inventory WHERE item_id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        // Catch foreign key constraint violation (SQLSTATE 23000)
        if ($e->getCode() == '23000') {
            die("Error: Cannot delete item. It is referenced in existing Requests or Purchase Orders. <a href='index.php'>Back to Inventory</a>");
        }
        // Rethrow other errors
        die("Database Error: " . $e->getMessage());
    }
}

header("Location: index.php");
exit;
<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once ROOT_PATH . 'config/db.php';

if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}

$id = $_GET['id'] ?? 0;

if ($id) {
    try {
        $stmt = $conn->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        // Catch foreign key constraint violation if supplier is used in orders
        die("Error: Cannot delete supplier. They may have associated purchase orders. <a href='index.php'>Back</a>");
    }
}

header("Location: index.php");
exit;
?>
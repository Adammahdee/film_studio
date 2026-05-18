<?php

use App\Core\Csrf;
use App\Core\DatabaseTransaction;
use App\Core\ErrorHandler;
use App\Core\AuditLogger;

// Role guard
$role = $_SESSION['role'] ?? '';
if ($role !== 'ADMIN' && $role !== 'MANAGER') {
    ErrorHandler::render403();
    exit();
}

// Enforce POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . url('inventory'));
    exit();
}

// CSRF check
if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
    error_log("CSRF failure on inventory delete. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
    ErrorHandler::render403();
    exit();
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['errors'] = "Invalid inventory item.";
    header("Location: " . url('inventory'));
    exit();
}

try {
    DatabaseTransaction::begin();

    // Fetch snapshot
    $stmt = $pdo->prepare("SELECT name FROM inventory WHERE item_id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        DatabaseTransaction::rollback();
        $_SESSION['errors'] = "Item not found.";
        header("Location: " . url('inventory'));
        exit();
    }

    // Delete record
    $delete = $pdo->prepare("DELETE FROM inventory WHERE item_id = ?");
    $delete->execute([$id]);

    // Audit log
    AuditLogger::log(
        'DELETE_INVENTORY',
        'inventory',
        $id,
        $item,
        null
    );

    DatabaseTransaction::commit();

    $_SESSION['success_msg'] = "Inventory item deleted successfully.";
    header("Location: " . url('inventory'));
    exit();

} catch (PDOException $e) {

    DatabaseTransaction::rollback();

    error_log("Inventory delete failed: " . $e->getMessage());

    if ($e->getCode() === '23000') {
        $_SESSION['errors'] = "Cannot delete item due to linked records.";
        header("Location: " . url('inventory'));
        exit();
    }

    ErrorHandler::render500("Database error occurred.", $e);
    exit();
}

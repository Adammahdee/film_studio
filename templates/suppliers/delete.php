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
    header("Location: " . url('suppliers'));
    exit();
}

// CSRF validation
if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
    error_log("CSRF failure on supplier delete. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
    ErrorHandler::render403();
    exit();
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['errors'] = "Invalid supplier identifier.";
    header("Location: " . url('suppliers'));
    exit();
}

try {
    DatabaseTransaction::begin();

    // Fetch supplier snapshot
    $stmt = $pdo->prepare("
        SELECT name, email 
        FROM suppliers 
        WHERE supplier_id = ?
    ");
    $stmt->execute([$id]);
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$supplier) {
        DatabaseTransaction::rollback();
        $_SESSION['errors'] = "Supplier not found.";
        header("Location: " . url('suppliers'));
        exit();
    }

    // Delete supplier
    $delete = $pdo->prepare("
        DELETE FROM suppliers 
        WHERE supplier_id = ?
    ");
    $delete->execute([$id]);

    // Audit log
    AuditLogger::log(
        'DELETE_SUPPLIER',
        'suppliers',
        $id,
        $supplier,
        null
    );

    DatabaseTransaction::commit();

    $_SESSION['success_msg'] = "Supplier deleted successfully.";
    header("Location: " . url('suppliers'));
    exit();

} catch (PDOException $e) {

    DatabaseTransaction::rollback();

    error_log("Supplier delete failed: " . $e->getMessage());

    if ($e->getCode() === '23000') {
        $_SESSION['errors'] = "Cannot delete supplier due to linked records.";
        header("Location: " . url('suppliers'));
        exit();
    }

    ErrorHandler::render500("Database error occurred.", $e);
    exit();
}

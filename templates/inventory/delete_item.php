<?php
use App\Core\Csrf;
use App\Core\DatabaseTransaction;
use App\Core\ErrorHandler;
use App\Services\AuditLogger;
require_once ROOT_PATH . "src/Auth/auth_check.php";
require_once ROOT_PATH . 'config/db.php';

if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}
// This will be replaced by Permissions::hasPermission later

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        // For now, just die. Later, use ErrorHandler::render403()
        die("Invalid CSRF token. Please try again.");
    }
    $id = $_POST['id'] ?? 0;

    if ($id) {
        try {
            DatabaseTransaction::begin();
            $itemToDelete = $conn->prepare("SELECT item_name FROM inventory WHERE item_id = ?"); // Fetch for logging
            $itemToDelete->execute([$id]);
            $oldItem = $itemToDelete->fetch(PDO::FETCH_ASSOC);
            $stmt = $conn->prepare("DELETE FROM inventory WHERE item_id = ?");
            $stmt->execute([$id]);
            AuditLogger::log('DELETE', 'Inventory', $id, $oldItem);
            DatabaseTransaction::commit();
        } catch (PDOException $e) {
            DatabaseTransaction::rollback();
            // Catch foreign key constraint violation (SQLSTATE 23000)
            if ($e->getCode() == '23000') {
                // For now, just die. Later, use ErrorHandler::render500() or a custom error page with a user-friendly message
                ErrorHandler::render500("Cannot delete item. It is referenced in existing Requests or Purchase Orders.", $e);
            }
            // Rethrow other errors
            die("Database Error: " . htmlspecialchars($e->getMessage()));
        }
    }
}

header("Location: " . url('inventory'));
exit;
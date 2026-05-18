<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Core\ErrorHandler;

// Role + auth guard
$role = $_SESSION['role'] ?? null;

if (!isset($_SESSION['user_id']) || ($role !== 'ADMIN' && $role !== 'MANAGER')) {
    header("Location: " . url('auth'));
    exit();
}

// Load DB config (should ideally be injected via bootstrap later)
require_once ROOT_PATH . "/config/db.php";

$successMessage = '';
$errorMessage = '';
$logs = [];

try {

    // Default maintenance logs fetch
    $stmt = $pdo->query("
        SELECT log_id, user_id, action, details, created_at
        FROM audit_logs
        ORDER BY log_id DESC
        LIMIT 20
    ");

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle POST actions (maintenance commands)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $action = $_POST['action'] ?? null;

        if ($action === 'clear_old_logs') {

            $delete = $pdo->prepare("
                DELETE FROM audit_logs
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");

            $delete->execute();

            $successMessage = "Old logs cleared successfully.";

            // refresh logs
            $stmt = $pdo->query("
                SELECT log_id, user_id, action, details, created_at
                FROM audit_logs
                ORDER BY log_id DESC
                LIMIT 20
            ");

            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

} catch (PDOException $e) {
    error_log("Maintenance module error: " . $e->getMessage());
    $errorMessage = "System maintenance error occurred.";
}

// View layer only
require_once ROOT_PATH . "/templates/dashboard/maintenance.php";
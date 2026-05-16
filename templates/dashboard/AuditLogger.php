<?php

namespace App\Services;

use PDO;
use PDOException;

class AuditLogger
{
    private static ?PDO $pdo = null;

    /**
     * Sets the PDO connection to be used for logging.
     * This should be called once, typically in public/index.php after the connection is established.
     *
     * @param PDO $pdo The PDO connection instance.
     */
    public static function setConnection(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * Logs an audit event.
     *
     * @param string $action The action performed (e.g., 'CREATE', 'UPDATE', 'DELETE', 'LOGIN').
     * @param string $module The module where the action occurred (e.g., 'Inventory', 'Suppliers', 'Auth').
     * @param int|null $recordId The ID of the record affected, if any.
     * @param array|null $oldValue The old state of the record (associative array), if applicable.
     * @param array|null $newValue The new state of the record (associative array), if applicable.
     */
    public static function log(string $action, string $module, ?int $recordId = null, ?array $oldValue = null, ?array $newValue = null): void
    {
        if (!self::$pdo) {
            error_log("AuditLogger: PDO connection not set. Cannot log event.");
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;
        $oldValueJson = $oldValue ? json_encode($oldValue) : null;
        $newValueJson = $newValue ? json_encode($newValue) : null;

        try {
            $stmt = self::$pdo->prepare("INSERT INTO audit_logs (user_id, action, module, record_id, old_value, new_value) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $action, $module, $recordId, $oldValueJson, $newValueJson]);
        } catch (PDOException $e) {
            error_log("AuditLogger Error: Failed to log event - " . $e->getMessage());
        }
    }
}
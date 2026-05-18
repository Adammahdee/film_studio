<?php
// src/Core/AuditLogger.php

namespace App\Core;

use PDO;
use PDOException;

class AuditLogger
{
    private static ?PDO $pdo = null;

    /**
     * Sets the PDO connection used by the logger.
     */
    public static function setConnection(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * Unified audit logger.
     * Preferred signature:
     *   log(string $action, string $module, ?int $recordId = null, ?array $oldValue = null, ?array $newValue = null)
     *
     * Backward compatible signature:
     *   log(PDO $pdo, string $action, string $module, ?int $recordId = null, ...$ignored)
     */
    public static function log(...$args): bool
    {
        $pdo = self::$pdo;

        // Backward compatibility for old calls that pass PDO as first argument
        if (isset($args[0]) && $args[0] instanceof PDO) {
            /** @var PDO $pdo */
            $pdo = array_shift($args);
        }

        if (!$pdo) {
            error_log("AuditLogger: PDO connection not set.");
            return false;
        }

        $action = (string)($args[0] ?? '');
        $module = (string)($args[1] ?? '');
        $recordId = isset($args[2]) ? (is_null($args[2]) ? null : (int)$args[2]) : null;
        $oldValue = isset($args[3]) && is_array($args[3]) ? $args[3] : null;
        $newValue = isset($args[4]) && is_array($args[4]) ? $args[4] : null;

        if ($action === '' || $module === '') {
            error_log("AuditLogger: action/module required.");
            return false;
        }

        $userId = $_SESSION['user_id'] ?? null;
        $oldValueJson = $oldValue ? json_encode($oldValue) : null;
        $newValueJson = $newValue ? json_encode($newValue) : null;
        $details = $module . ($recordId ? " (ID: {$recordId})" : '');

        try {
            // Keep both tables in sync with existing project usage.
            $stmtAudit = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, module, record_id, old_value, new_value, details)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtAudit->execute([$userId, $action, $module, $recordId, $oldValueJson, $newValueJson, $details]);

            $stmtApprovals = $pdo->prepare("
                INSERT INTO approvals (user_id, action, details, status, created_at)
                VALUES (:user_id, :action, :details, 'APPROVED', NOW())
            ");
            $stmtApprovals->execute([
                ':user_id' => $userId,
                ':action' => $action,
                ':details' => $details
            ]);

            return true;
        } catch (PDOException $e) {
            error_log("AuditLogger Failure: " . $e->getMessage());
            return false;
        }
    }
}

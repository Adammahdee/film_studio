<?php

namespace App\Core;

use PDO;
use PDOException;

class DatabaseTransaction
{
    private static ?PDO $pdo = null;

    /**
     * Sets the PDO connection to be used for transactions.
     * This should be called once, typically in public/index.php after the connection is established.
     *
     * @param PDO $pdo The PDO connection instance.
     */
    public static function setConnection(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * Begins a new database transaction.
     *
     * @throws Exception If the PDO connection is not set or transaction cannot be started.
     */
    public static function begin(): void
    {
        if (!self::$pdo) {
            throw new \Exception("PDO connection not set for DatabaseTransaction.");
        }
        self::$pdo->beginTransaction();
    }

    /**
     * Commits the current database transaction.
     */
    public static function commit(): void
    {
        self::$pdo->commit();
    }

    /**
     * Rolls back the current database transaction.
     */
    public static function rollback(): void
    {
        self::$pdo->rollBack();
    }
}
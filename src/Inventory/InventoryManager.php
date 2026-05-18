<?php
// src/Inventory/InventoryManager.php

namespace App\Inventory;

use PDO;
use Exception;

class InventoryManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Fetch all items in stock (aligned with actual schema).
     */
    public function getAllItems(): array
    {
        $sql = "SELECT item_id, name, quantity, status, created_at
                FROM inventory
                ORDER BY name ASC";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Safely adjust stock levels up or down (transaction safe).
     */
    public function updateStock(int $itemId, int $quantityChange): bool
    {
        try {
            $this->pdo->beginTransaction();

            // STEP 1: Lock row
            $stmt = $this->pdo->prepare("
                SELECT quantity
                FROM inventory
                WHERE item_id = :item_id
                FOR UPDATE
            ");

            $stmt->execute([
                ':item_id' => $itemId
            ]);

            $current = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$current) {
                throw new Exception("Item not found");
            }

            $currentQuantity = (int)$current['quantity'];
            $newQuantity = $currentQuantity + $quantityChange;

            // STEP 2: Prevent negative stock
            if ($newQuantity < 0) {
                throw new Exception("Insufficient stock");
            }

            // STEP 3: Atomic update
            $update = $this->pdo->prepare("
                UPDATE inventory
                SET quantity = quantity + :quantityChange,
                    status = CASE
                        WHEN (quantity + :quantityChange) > 0 THEN 'AVAILABLE'
                        ELSE 'OUT_OF_STOCK'
                    END
                WHERE item_id = :item_id
            ");

            $update->execute([
                ':quantityChange' => $quantityChange,
                ':item_id' => $itemId
            ]);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Stock update failed: " . $e->getMessage());
            return false;
        }
    }
}

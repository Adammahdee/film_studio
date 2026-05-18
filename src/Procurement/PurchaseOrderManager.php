<?php
// src/Procurement/PurchaseOrderManager.php

namespace App\Procurement;

use PDO;
use Exception;

class PurchaseOrderManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Creates a full purchase order with itemized details.
     */
    public function createOrder(int $supplierId, int $createdBy, array $items, float $totalAmount): bool
    {
        try {
            if (empty($items)) {
                throw new Exception("Purchase order must contain at least one item.");
            }

            $this->pdo->beginTransaction();

            // STEP 1: Insert purchase order header
            $stmt = $this->pdo->prepare("
                INSERT INTO purchase_orders
                (supplier_id, total_amount, status, created_by, order_date)
                VALUES
                (:supplier_id, :total_amount, 'PENDING', :created_by, NOW())
            ");

            $stmt->execute([
                ':supplier_id'  => $supplierId,
                ':total_amount' => $totalAmount,
                ':created_by'   => $createdBy
            ]);

            $purchaseOrderId = (int)$this->pdo->lastInsertId();

            // STEP 2: Insert order items
            $itemStmt = $this->pdo->prepare("
                INSERT INTO purchase_order_items
                (purchase_order_id, item_id, quantity, unit_price)
                VALUES
                (:purchase_order_id, :item_id, :quantity, :unit_price)
            ");

            foreach ($items as $item) {
                $quantity = (int)($item['quantity'] ?? 0);
                $unitPrice = (float)($item['unit_price'] ?? -1);
                $itemId = (int)($item['item_id'] ?? 0);

                if ($itemId <= 0 || $quantity <= 0 || $unitPrice < 0) {
                    throw new Exception("Invalid purchase order item payload.");
                }

                $itemStmt->execute([
                    ':purchase_order_id' => $purchaseOrderId,
                    ':item_id'           => $itemId,
                    ':quantity'          => $quantity,
                    ':unit_price'        => $unitPrice
                ]);
            }

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("PurchaseOrderManager Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch all purchase orders with supplier and creator info.
     */
    public function getAllOrders(): array
    {
        $sql = "
            SELECT
                po.*,
                s.name AS supplier_name,
                u.full_name AS creator_name
            FROM purchase_orders po
            INNER JOIN suppliers s ON po.supplier_id = s.supplier_id
            INNER JOIN users u ON po.created_by = u.user_id
            ORDER BY po.order_date DESC
        ";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}

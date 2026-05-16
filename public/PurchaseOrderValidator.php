<?php

namespace App\Validation;

class PurchaseOrderValidator
{
    /**
     * Validates data for creating a purchase order.
     *
     * @param array $data The data to validate (e.g., $_POST).
     * @return array An array of error messages, empty if valid.
     */
    public static function validateCreatePurchaseOrder(array $data): array
    {
        $errors = [];

        // Validate supplier_id
        if (empty($data['supplier_id'])) {
            $errors['supplier_id'] = "Supplier is required.";
        } elseif (!is_numeric($data['supplier_id']) || (int)$data['supplier_id'] <= 0) {
            $errors['supplier_id'] = "Invalid supplier selected.";
        }

        // Validate items (assuming 'items' is an array of item_id and quantity)
        if (empty($data['items']) || !is_array($data['items'])) {
            $errors['items'] = "At least one item is required for the purchase order.";
        } else {
            foreach ($data['items'] as $index => $item) {
                if (empty($item['item_id']) || !is_numeric($item['item_id']) || (int)$item['item_id'] <= 0) {
                    $errors["items[$index][item_id]"] = "Invalid item selected for row " . ($index + 1) . ".";
                }
                if (empty($item['quantity']) || !is_numeric($item['quantity']) || (int)$item['quantity'] <= 0) {
                    $errors["items[$index][quantity]"] = "Quantity must be a positive number for row " . ($index + 1) . ".";
                }
                if (!empty($item['price']) && (!is_numeric($item['price']) || (float)$item['price'] <= 0)) {
                    $errors["items[$index][price]"] = "Price must be a positive number for row " . ($index + 1) . ".";
                }
            }
        }

        return $errors;
    }

    // You can add more specific validation methods if needed
}
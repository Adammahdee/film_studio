<?php

namespace App\Validation;

class InventoryValidator
{
    /**
     * Validates data for adding or editing an inventory item.
     *
     * @param array $data The data to validate (e.g., $_POST).
     * @return array An array of error messages, empty if valid.
     */
    public static function validateItem(array $data): array
    {
        $errors = [];

        // Validate item_name
        if (empty($data['item_name'])) {
            $errors['item_name'] = "Item name is required.";
        } elseif (strlen($data['item_name']) < 2 || strlen($data['item_name']) > 100) {
            $errors['item_name'] = "Item name must be between 2 and 100 characters.";
        }

        // Validate quantity
        if (!isset($data['quantity']) || $data['quantity'] === '') {
            $errors['quantity'] = "Quantity is required.";
        } elseif (!is_numeric($data['quantity'])) {
            $errors['quantity'] = "Quantity must be a number.";
        } elseif ((int)$data['quantity'] < 0) {
            $errors['quantity'] = "Quantity cannot be negative.";
        }

        return $errors;
    }

    // You can add more specific validation methods if needed, e.g., for unique item names
    // public static function validateUniqueItemName(string $itemName, ?int $excludeId = null): bool {
    //     // Logic to check database for unique item name
    //     return true;
    // }
}
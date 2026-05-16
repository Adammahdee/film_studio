<?php

namespace App\Validation;

class SupplierValidator
{
    /**
     * Validates data for adding or editing a supplier.
     *
     * @param array $data The data to validate (e.g., $_POST).
     * @return array An array of error messages, empty if valid.
     */
    public static function validateSupplier(array $data): array
    {
        $errors = [];

        // Validate name
        if (empty($data['name'])) {
            $errors['name'] = "Supplier name is required.";
        } elseif (strlen($data['name']) < 2 || strlen($data['name']) > 100) {
            $errors['name'] = "Supplier name must be between 2 and 100 characters.";
        }

        // Validate contact_person
        if (empty($data['contact_person'])) {
            $errors['contact_person'] = "Contact person is required.";
        } elseif (strlen($data['contact_person']) < 2 || strlen($data['contact_person']) > 100) {
            $errors['contact_person'] = "Contact person must be between 2 and 100 characters.";
        }

        // Validate phone
        if (empty($data['phone'])) {
            $errors['phone'] = "Phone number is required.";
        } elseif (!preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', $data['phone'])) { // Basic phone format
            $errors['phone'] = "Invalid phone number format.";
        }

        // Validate email (optional but if provided, must be valid)
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Invalid email address format.";
        }

        return $errors;
    }

    // You can add more specific validation methods if needed
}
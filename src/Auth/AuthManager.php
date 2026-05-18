<?php
// src/Auth/AuthManager.php

namespace App\Auth;

use PDO;

class AuthManager
{
    private PDO $pdo;

    /**
     * Inject the central PDO connection instance directly upon initialization.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Verifies user credentials against the database using password_verify.
     * Returns the user record array on success, or false on failure.
     */
    public function login(string $fullName, string $password): ?array
    {
        // Query the columns we saw in your database snapshot
        $stmt = $this->pdo->prepare("
            SELECT user_id, full_name, password, role 
            FROM users 
            WHERE full_name = :full_name 
            LIMIT 1
        ");
        
        $stmt->execute([':full_name' => $fullName]);
        $user = $stmt->fetch();

        // Safely evaluate cryptographically hashed passwords
        if ($user && password_verify($password, $user['password'])) {
            // Strip the hashed password array element out before passing it to the session memory
            unset($user['password']);
            return $user;
        }

        return null;
    }
}
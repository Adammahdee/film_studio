<?php

namespace App\Security;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {

            // Check if we are running on local Laragon vs production HTTPS
            $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

            session_set_cookie_params([
                'lifetime' => 0, // Session cookie expires when browser closes
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            session_start();
        }
    }

    /**
     * Retrieves the stable CSRF token from the session, or generates one if missing.
     * This protects against infinite regeneration bugs.
     */
    public static function getCsrfToken(): string
    {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validates an incoming form token against the securely stored session anchor.
     */
    public static function validateCsrfToken(?string $token): bool
    {
        self::start();
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
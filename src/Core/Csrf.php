<?php

namespace App\Core;

class Csrf
{
    private const TOKEN_NAME = 'csrf_token';

    /**
     * Generates a new CSRF token and stores it in the session.
     *
     * @return string The generated CSRF token.
     */
    public static function generateToken(): string
    {
        if (empty($_SESSION[self::TOKEN_NAME])) {
            $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_NAME];
    }

    /**
     * Validates a submitted CSRF token against the one stored in the session.
     *
     * @param string $submittedToken The token submitted with the request.
     * @return bool True if the token is valid, false otherwise.
     */
    public static function validateToken(string $submittedToken): bool
    {
        if (empty($_SESSION[self::TOKEN_NAME]) || !hash_equals($_SESSION[self::TOKEN_NAME], $submittedToken)) {
            return false;
        }
        // Token is valid, regenerate it to prevent "replay" attacks
        self::regenerateToken();
        return true;
    }

    /**
     * Regenerates the CSRF token in the session.
     */
    public static function regenerateToken(): void
    {
        $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(32));
    }
}
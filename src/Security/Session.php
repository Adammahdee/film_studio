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
}
<?php

use App\Security\Session;

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Optional: clear remember-me cookie safely
setcookie('remember_me', '', time() - 3600, "/", "", isset($_SERVER['HTTPS']), true);

// Clear session data fully
$_SESSION = [];

// Destroy session
session_destroy();

// Redirect to login page
header("Location: " . url('auth'));
exit();
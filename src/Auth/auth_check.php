<?php

// 1. MANUALLY INCLUDE NAMESPACED CLASSES (Fallback for environments without active/updated Autoloader)
require_once ROOT_PATH . 'src/Security/Session.php';
require_once ROOT_PATH . 'src/Core/ErrorHandler.php';
require_once ROOT_PATH . 'src/Auth/Permissions.php';

use App\Security\Session;
use App\Core\ErrorHandler;

// Ensure session is active via secure wrapper
Session::start();

// Get the current page from the router parameter
$currentPage = $_GET['page'] ?? 'dashboard';

// 1. CRITICAL AUTH CHECK: If not logged in, redirect ONLY if they aren't already trying to authenticate
if (!isset($_SESSION['user_id'])) {
    if ($currentPage !== 'auth') {
        // Redirect to login instead of showing a forbidden error
        header("Location: " . url('auth'));
        exit();
    }
}

// 2. MAINTENANCE MODE CHECK
// Using ROOT_PATH ensures this loader is always found regardless of folder depth
require_once ROOT_PATH . "templates/includes/settings_loader.php"; 

if (isset($maintenance_mode) && $maintenance_mode && ($_SESSION['role'] ?? '') !== 'ADMIN') {
    // Stop loops by checking our router variable instead of the filename
    if (!in_array($currentPage, ['logout', 'maintenance', 'auth'])) {
        ErrorHandler::render403("The system is currently under maintenance.");
        exit();
    }
}
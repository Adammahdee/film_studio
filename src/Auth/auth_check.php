<?php

require_once ROOT_PATH . 'src/Security/Session.php';
require_once ROOT_PATH . 'src/Core/ErrorHandler.php';

use App\Security\Session;
use App\Core\ErrorHandler;

Session::start();

// Current route from front controller
$currentPage = $_GET['page'] ?? 'dashboard';

// Load system settings (maintenance flag)
require_once ROOT_PATH . 'templates/includes/settings_loader.php';

/**
 * 1. AUTHENTICATION GUARD
 * Allow only auth page when not logged in
 */
if (!isset($_SESSION['user_id'])) {

    if ($currentPage !== 'auth') {
        header("Location: " . url('auth'));
        exit();
    }
}

/**
 * 2. MAINTENANCE MODE GUARD
 * Admin bypass allowed
 */
$role = $_SESSION['role'] ?? '';

if (
    !empty($maintenance_mode) &&
    $maintenance_mode === true &&
    $role !== 'ADMIN'
) {
    $allowedDuringMaintenance = ['auth', 'logout', 'maintenance'];

    if (!in_array($currentPage, $allowedDuringMaintenance, true)) {
        ErrorHandler::render403("System is under maintenance.");
        exit();
    }
}
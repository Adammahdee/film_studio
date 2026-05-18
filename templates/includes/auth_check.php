<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load system settings
require_once __DIR__ . "/settings_loader.php";

/**
 * AUTH GUARD
 */
if (!isset($_SESSION['user_id'])) {
    header("Location: " . url('auth'));
    exit();
}

/**
 * MAINTENANCE GUARD
 * - Admin bypass allowed
 */
$role = $_SESSION['role'] ?? '';

if (!empty($maintenance_mode) && $maintenance_mode && $role !== 'ADMIN') {

    $currentPage = basename($_SERVER['PHP_SELF']);

    // Prevent redirect loops for allowed pages
    $allowedPages = ['logout.php', 'maintenance.php'];

    if (!in_array($currentPage, $allowedPages)) {
        header("Location: " . url('maintenance'));
        exit();
    }
}
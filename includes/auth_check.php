<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /film_studio/auth/login.php");
    exit();
}

// Check for maintenance mode
require_once __DIR__ . "/settings_loader.php";
if (isset($maintenance_mode) && $maintenance_mode && ($_SESSION['role'] ?? '') !== 'ADMIN') {
    // Allow access to logout and maintenance page to avoid loops
    $currentPage = basename($_SERVER['PHP_SELF']);
    if (!in_array($currentPage, ['logout.php', 'maintenance.php'])) {
        header("Location: /film_studio/maintenance.php");
        exit();
    }
}
?>

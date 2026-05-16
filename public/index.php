<?php
/**
 * FILM STUDIO PROCUREMENT & INVENTORY MANAGEMENT SYSTEM
 * Main Application Entry Point (Front Controller Gateway)
 */

declare(strict_types=1);

// Start secure session management
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => isset($_SERVER['HTTPS']),
        'use_strict_mode' => true,
    ]);
}

// Define the absolute base path of the project root directory
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// 1. Setup Autoloading (Once Composer is initialized)
if (file_exists(ROOT_PATH . 'vendor/autoload.php')) {
    require_once ROOT_PATH . 'vendor/autoload.php';
}

// 2. Load Global Database/App Configurations
if (file_exists(ROOT_PATH . 'config/database.php')) {
    require_once ROOT_PATH . 'config/database.php';
}

// 3. Simple URL Router (Example routing query strings or URI paths)
$page = $_GET['page'] ?? 'dashboard';

// Clean routing logic to capture layout sections safely
switch ($page) {
    case 'auth':
        // Looks inside templates/auth/ for your legacy files
        require_once ROOT_PATH . 'templates/auth/index.php'; 
        break;
        
    case 'inventory':
        require_once ROOT_PATH . 'templates/inventory/index.php';
        break;
        
    case 'requests':
        require_once ROOT_PATH . 'templates/requests/index.php';
        break;

    case 'suppliers':
        require_once ROOT_PATH . 'templates/suppliers/index.php';
        break;

    case 'dashboard':
    default:
        // Points to your dashboard directory template
        require_once ROOT_PATH . 'templates/dashboard/index.php';
        break;
}
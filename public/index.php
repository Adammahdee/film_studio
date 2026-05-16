<?php
declare(strict_types=1);

// --- REDIRECT LOOP DETECTOR BLOCK ---
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Track how many times this entry point has been hit in this session sequence
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Time-sensitive loop detection: Reset counter if more than 2 seconds since last hit.
// This distinguishes between an automated redirect loop and normal user navigation.
$now = time();
$lastHit = $_SESSION['last_redirect_time'] ?? 0;
if ($now - $lastHit > 2) {
    $_SESSION['redirect_hop_count'] = 0;
}
$_SESSION['last_redirect_time'] = $now;
$_SESSION['redirect_hop_count'] = ($_SESSION['redirect_hop_count'] ?? 0) + 1;

// Increase limit to 6 to allow for complex legitimate redirect chains (e.g. root -> gateway -> auth -> dashboard)
if ($_SESSION['redirect_hop_count'] > 6) {
    $_SESSION['redirect_hop_count'] = 0; // Reset counter for next test
    
    echo "<div style='font-family:sans-serif; padding:20px; border:3px solid #ff4d4d; background:#fff2f2; border-radius:8px;'>";
    echo "<h1 style='color:#cc0000; margin-top:0;'>🛑 Redirect Loop Halted by Security Guard</h1>";
    echo "<p>Your application tried to redirect too many times in a row.</p>";
    echo "<h3>Current Routing Properties:</h3>";
    echo "<ul>";
    echo "<li><strong>Target Page Parameter (\$page):</strong> <code>" . htmlspecialchars($_GET['page'] ?? 'dashboard') . "</code></li>";
    echo "<li><strong>Full Request URI:</strong> <code>" . htmlspecialchars($_SERVER['REQUEST_URI']) . "</code></li>";
    echo "<li><strong>User ID Session Status:</strong> " . (isset($_SESSION['user_id']) ? "Logged In (ID: ".$_SESSION['user_id'].")" : "Not Logged In") . "</li>";
    echo "</ul>";
    
    echo "<h3>Execution Trace (Files compiled before the crash):</h3>";
    echo "<pre style='background:#dfdfdf; padding:15px; border-radius:4px; overflow-x:auto;'>";
    foreach (get_included_files() as $index => $file) {
        echo "[" . ($index + 1) . "] $file\n";
    }
    echo "</pre>";
    echo "</div>";
    exit();
}
// --- END DETECTOR BLOCK ---

/**
 * FILM STUDIO PROCUREMENT & INVENTORY MANAGEMENT SYSTEM
 * Main Application Entry Point (Front Controller Gateway)
 */

// Define the absolute base path of the project root directory
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

// 1. Setup Autoloading
if (file_exists(ROOT_PATH . 'vendor/autoload.php')) {
    require_once ROOT_PATH . 'vendor/autoload.php';
}

// 1.5 Load Helper Functions (Required for URL routing and auth checks)
require_once ROOT_PATH . 'templates/includes/functions.php';

// 2. Load Global Database Configurations
require_once ROOT_PATH . 'config/db.php';

// 2.5 Database Connection Health Check
try {
    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new Exception("Database connection variable (\$conn) is missing or invalid.");
    }
    // Perform a lightweight query to verify the connection is alive
    $conn->query("SELECT 1");
} catch (Exception $e) {
    http_response_code(500);
    die("<h1>System Unavailable</h1><p>We are experiencing technical difficulties connecting to the database. Please try again later.</p><!-- Debug Error: " . htmlspecialchars($e->getMessage()) . " -->");
}

// 3. Simple URL Router
$page = $_GET['page'] ?? 'dashboard';

switch ($page) {
    case 'auth':
        require_once ROOT_PATH . 'templates/auth/index.php'; 
        break;
        
    case 'inventory':
        require_once ROOT_PATH . 'templates/inventory/index.php';
        break;
        
    case 'landing':
        require_once ROOT_PATH . 'templates/landing/index.php';
        break;

    case 'requests':
        $action = $_GET['action'] ?? 'index';
        if ($action === 'create') {
            require_once ROOT_PATH . 'templates/requests/create.php';
        } elseif ($action === 'approve') {
            require_once ROOT_PATH . 'templates/requests/approve.php';
        } elseif ($action === 'my_requests') {
            require_once ROOT_PATH . 'templates/requests/my_requests.php';
        } elseif ($action === 'store') {
            require_once ROOT_PATH . 'templates/requests/store.php';
        } else {
            require_once ROOT_PATH . 'templates/requests/index.php';
        }
        break;

    case 'suppliers':
        $action = $_GET['action'] ?? 'index';
        if ($action === 'edit') {
            require_once ROOT_PATH . 'templates/suppliers/edit.php';
        } else {
            require_once ROOT_PATH . 'templates/suppliers/index.php';
        }
        break;

    case 'settings':
        // FIX: Pointing to the new template migration directory
        require_once ROOT_PATH . 'templates/dashboard/settings.php';
        break;

    case 'backup':
        require_once ROOT_PATH . 'templates/admin/backup.php';
        break;

    case 'maintenance':
        require_once ROOT_PATH . 'templates/errors/maintenance.php';
        break;

    case 'reports':
        require_once ROOT_PATH . 'templates/reports/index.php';
        break;

    case 'profile':
        // FIX: Pointing to the new template migration directory
        require_once ROOT_PATH . 'templates/dashboard/profile.php';
        break;
// ... Existing cases (reports, profile, etc.) ...

    case 'purchase_orders':
        // Capture the internal action parameter (defaults to listing page)
        $action = $_GET['action'] ?? 'index';

        if ($action === 'create') {
            require_once ROOT_PATH . 'templates/purchase_orders/create.php';
        } elseif ($action === 'store') {
            require_once ROOT_PATH . 'templates/purchase_orders/store.php';
        } else {
            // Default sub-route maps to your index/list layout view
            require_once ROOT_PATH . 'templates/purchase_orders/index.php';
        }
        break;

    case 'dashboard':
    default:
        require_once ROOT_PATH . 'templates/dashboard/index.php';
        break;
}
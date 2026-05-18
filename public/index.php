<?php
declare(strict_types=1);

/**
 * FILM STUDIO PROCUREMENT & INVENTORY MANAGEMENT SYSTEM
 * Front Controller Gateway
 */

// ---------------------------------------------------
// 1. ROOT PATH + AUTOLOADER
// ---------------------------------------------------

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

require_once ROOT_PATH . 'vendor/autoload.php';
require_once ROOT_PATH . 'config/db.php';
require_once ROOT_PATH . 'templates/includes/functions.php';

use App\Security\Session;
use App\Core\DatabaseTransaction;
use App\Core\AuditLogger;

// ---------------------------------------------------
// 2. START SECURE SESSION
// ---------------------------------------------------

Session::start();
DatabaseTransaction::setConnection($pdo);
AuditLogger::setConnection($pdo);

// ---------------------------------------------------
// 3. ROUTING PARAMETERS
// ---------------------------------------------------

$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? null;

// ---------------------------------------------------
// 4. PUBLIC ROUTES
// ---------------------------------------------------

$publicPages = ['auth', 'login'];

// ---------------------------------------------------
// 5. AUTHENTICATION GUARD
// ---------------------------------------------------

if (
    !in_array($page, $publicPages) &&
    !isset($_SESSION['user_id'])
) {
    header('Location: ' . url('auth'));
    exit();
}

// ---------------------------------------------------
// 6. LOAD GLOBAL HEADER
// ---------------------------------------------------

require_once ROOT_PATH . 'templates/includes/header.php';

// ---------------------------------------------------
// 7. ROUTER SWITCH
// ---------------------------------------------------

switch ($page) {

    // -----------------------------------------------
    // AUTH
    // -----------------------------------------------

    case 'auth':
    case 'login':
        require_once ROOT_PATH . 'templates/auth/index.php';
        break;

    // -----------------------------------------------
    // DASHBOARD
    // -----------------------------------------------

    case 'dashboard':
        require_once ROOT_PATH . 'templates/dashboard/index.php';
        break;

    // -----------------------------------------------
    // INVENTORY
    // -----------------------------------------------

    case 'inventory':

        switch ($action) {

            case 'add':
                require_once ROOT_PATH . 'templates/inventory/add_item.php';
                break;

            case 'edit':
                require_once ROOT_PATH . 'templates/inventory/edit_item.php';
                break;

            case 'delete':
                require_once ROOT_PATH . 'templates/inventory/delete_item.php';
                break;

            default:
                require_once ROOT_PATH . 'templates/inventory/index.php';
                break;
        }

        break;

    // -----------------------------------------------
    // PURCHASE ORDERS
    // -----------------------------------------------

    case 'purchase_orders':
    case 'procurement':

        switch ($action) {

            case 'create':
                require_once ROOT_PATH . 'templates/purchase_orders/create.php';
                break;

            case 'store':
                require_once ROOT_PATH . 'templates/purchase_orders/store.php';
                break;

            case 'view':
                require_once ROOT_PATH . 'templates/purchase_orders/view.php';
                break;

            case 'receive':
                require_once ROOT_PATH . 'templates/purchase_orders/receive.php';
                break;

            default:
                require_once ROOT_PATH . 'templates/purchase_orders/index.php';
                break;
        }

        break;

    // -----------------------------------------------
    // REQUESTS
    // -----------------------------------------------

    case 'requests':

        switch ($action) {

            case 'create':
                require_once ROOT_PATH . 'templates/requests/create.php';
                break;

            case 'store':
                require_once ROOT_PATH . 'templates/requests/store.php';
                break;

            case 'approve':
                require_once ROOT_PATH . 'templates/requests/approve.php';
                break;

            case 'process':
                require_once ROOT_PATH . 'templates/requests/approve.php';
                break;

            case 'my_requests':
                require_once ROOT_PATH . 'templates/requests/my_requests.php';
                break;

            default:
                require_once ROOT_PATH . 'templates/requests/index.php';
                break;
        }

        break;

    // -----------------------------------------------
    // SUPPLIERS
    // -----------------------------------------------

    case 'suppliers':

        switch ($action) {

            case 'create':
                require_once ROOT_PATH . 'templates/suppliers/create.php';
                break;

            case 'edit':
                require_once ROOT_PATH . 'templates/suppliers/edit.php';
                break;

            case 'delete':
                require_once ROOT_PATH . 'templates/suppliers/delete.php';
                break;

            default:
                require_once ROOT_PATH . 'templates/suppliers/index.php';
                break;
        }

        break;

    // -----------------------------------------------
    // USERS
    // -----------------------------------------------

    case 'users':

        switch ($action) {

            case 'create':
            case null:
                require_once ROOT_PATH . 'templates/users/create.php';
                break;

            default:
                http_response_code(404);
                require_once ROOT_PATH . 'templates/errors/404.php';
                break;
        }

        break;

    // -----------------------------------------------
    // REPORTS
    // -----------------------------------------------

    case 'reports':

    $stmt = $pdo->query("
        SELECT 
            COUNT(*) AS total,
            SUM(status = 'APPROVED') AS approved,
            SUM(status = 'PENDING') AS pending,
            SUM(status = 'REJECTED') AS rejected
        FROM requests
    ");

    $viewData['requestMetrics'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'total' => 0,
        'approved' => 0,
        'pending' => 0,
        'rejected' => 0
    ];

    require ROOT_PATH . 'templates/reports/index.php';
    break;

    // -----------------------------------------------
    // PROFILE
    // -----------------------------------------------

    case 'profile':
        require_once ROOT_PATH . 'templates/dashboard/profile.php';
        break;

    // -----------------------------------------------
    // SETTINGS
    // -----------------------------------------------

    case 'settings':
        require_once ROOT_PATH . 'templates/dashboard/settings.php';
        break;

    // -----------------------------------------------
    // BACKUP
    // -----------------------------------------------

    case 'backup':
        require_once ROOT_PATH . 'templates/admin/backup.php';
        break;

    // -----------------------------------------------
    // MAINTENANCE
    // -----------------------------------------------

    case 'maintenance':
        require_once ROOT_PATH . 'templates/admin/maintenance.php';
        break;

    // -----------------------------------------------
    // LOGOUT
    // -----------------------------------------------

    case 'logout':

        session_unset();
        session_destroy();

        header('Location: ' . url('auth'));
        exit();

    // -----------------------------------------------
    // 404
    // -----------------------------------------------

    default:
        http_response_code(404);
        require_once ROOT_PATH . 'templates/errors/404.php';
        break;
}

// ---------------------------------------------------
// 8. LOAD GLOBAL FOOTER
// ---------------------------------------------------

require_once ROOT_PATH . 'templates/includes/footer.php';

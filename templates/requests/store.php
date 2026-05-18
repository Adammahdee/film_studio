<?php

use App\Core\Csrf;
use App\Core\ErrorHandler;
use App\Core\DatabaseTransaction;
use App\Core\AuditLogger;
use App\Security\Session;

// Ensure session is active (front controller should ideally handle this globally)
Session::start();

// Auth guard
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: " . url('auth'));
    exit();
}

// Enforce POST only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . url('requests', 'create'));
    exit();
}

// CSRF validation
if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
    error_log("CSRF validation failed. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
    ErrorHandler::render403();
    exit();
}

// Input extraction
$itemId = (int)($_POST['item_id'] ?? 0);
$qty = (int)($_POST['quantity'] ?? 0);

// Input validation
if ($itemId <= 0 || $qty <= 0) {
    $_SESSION['errors'] = "Invalid request parameters.";
    header("Location: " . url('requests', 'create'));
    exit();
}

try {
    DatabaseTransaction::begin();

    $itemNameColumn = 'name';
    $nameColStmt = $pdo->query("SHOW COLUMNS FROM inventory LIKE 'name'");
    if (!$nameColStmt->fetch(PDO::FETCH_ASSOC)) {
        $itemNameColumn = 'item_name';
    }

    // Lock item row
    $sql = sprintf("
        SELECT %s AS item_name, quantity, status 
        FROM inventory 
        WHERE item_id = ? 
        FOR UPDATE
    ", $itemNameColumn);
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        throw new Exception("Item not found in inventory.");
    }

    $currentStock = (int)$item['quantity'];

    if ($qty > $currentStock) {
        throw new Exception("Requested quantity exceeds available stock.");
    }

    // Insert request (supports schemas with/without requests.created_at)
    $hasCreatedAt = false;
    $createdAtColStmt = $pdo->query("SHOW COLUMNS FROM requests LIKE 'created_at'");
    if ($createdAtColStmt->fetch(PDO::FETCH_ASSOC)) {
        $hasCreatedAt = true;
    }

    if ($hasCreatedAt) {
        $insert = $pdo->prepare("
            INSERT INTO requests (user_id, item_id, quantity, status, created_at)
            VALUES (?, ?, ?, 'PENDING', NOW())
        ");
        $insert->execute([$user_id, $itemId, $qty]);
    } else {
        $insert = $pdo->prepare("
            INSERT INTO requests (user_id, item_id, quantity, status)
            VALUES (?, ?, ?, 'PENDING')
        ");
        $insert->execute([$user_id, $itemId, $qty]);
    }

    $requestId = $pdo->lastInsertId();

    // Audit log
    AuditLogger::log(
        'CREATE_REQUEST',
        'requests',
        $requestId,
        null,
        [
            'item_id' => $itemId,
            'item_name' => $item['item_name'],
            'quantity' => $qty,
            'status' => 'PENDING'
        ]
    );

    DatabaseTransaction::commit();

    $_SESSION['success_msg'] = "Request created successfully.";
    header("Location: " . url('requests'));
    exit();

} catch (Exception $e) {
    DatabaseTransaction::rollback();

    error_log("Request creation failed: " . $e->getMessage());

    $_SESSION['errors'] = $e->getMessage();
    header("Location: " . url('requests', 'create'));
    exit();
}

<?php
/**
 * Purchase Order Receiving Controller
 * File: templates/purchase_orders/receive.php
 */

use App\Core\ErrorHandler;
use App\Core\DatabaseTransaction;
use App\Security\Session;

// Secure session bootstrap
Session::start();

// Authentication protection
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('auth'));
    exit();
}

// Role authorization
$role = $_SESSION['role'] ?? '';

if (!in_array($role, ['ADMIN', 'MANAGER'])) {
    ErrorHandler::render403();
    exit();
}

// Capture purchase order ID safely
$id = (int) ($_GET['id'] ?? 0);

$errorMessage = '';
$successMessage = '';

// Validate ID early
if ($id <= 0) {
    $errorMessage = 'Invalid purchase order reference.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {

    // CSRF validation
    if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Invalid security token. Please refresh and try again.';
    } else {

        try {

            DatabaseTransaction::begin();

            /**
             * Fetch current purchase order
             */
            $stmt = $pdo->prepare("
                SELECT purchase_order_id, status
                FROM purchase_orders
                WHERE purchase_order_id = ?
                FOR UPDATE
            ");

            $stmt->execute([$id]);

            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new Exception('Purchase order not found.');
            }

            if (strtoupper($order['status']) !== 'PENDING') {
                throw new Exception('This purchase order has already been processed.');
            }

            /**
             * Fetch purchase order items
             */
            $itemsStmt = $pdo->prepare("
                SELECT item_id, quantity
                FROM purchase_order_items
                WHERE purchase_order_id = ?
            ");

            $itemsStmt->execute([$id]);

            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($items)) {
                throw new Exception('Purchase order has no line items to receive.');
            }

            /**
             * Update inventory stock levels
             */
            $inventoryStmt = $pdo->prepare("
                UPDATE inventory
                SET quantity = quantity + ?,
                    status = CASE
                        WHEN (quantity + ?) > 0 THEN 'AVAILABLE'
                        ELSE 'OUT_OF_STOCK'
                    END
                WHERE item_id = ?
            ");

            foreach ($items as $item) {

                $inventoryStmt->execute([
                    (int) $item['quantity'],
                    (int) $item['quantity'],
                    (int) $item['item_id']
                ]);

                if ($inventoryStmt->rowCount() === 0) {
                    throw new Exception('Inventory item not found for stock update.');
                }
            }

            /**
             * Mark purchase order as received
             */
            $updateOrderStmt = $pdo->prepare("
                UPDATE purchase_orders
                SET status = 'RECEIVED'
                WHERE purchase_order_id = ?
            ");

            $updateOrderStmt->execute([$id]);

            DatabaseTransaction::commit();

            // Clean redirect
            header('Location: ' . url('purchase_orders'));
            exit();

        } catch (Exception $e) {

            DatabaseTransaction::rollback();

            $errorMessage = $e->getMessage();
        }
    }
}

/**
 * Load purchase order details for confirmation screen
 */
$poDetails = null;

if ($id > 0) {

    $detailsStmt = $pdo->prepare("
        SELECT 
            po.purchase_order_id,
            po.status,
            po.order_date,
            s.name AS supplier_name
        FROM purchase_orders po
        LEFT JOIN suppliers s 
            ON po.supplier_id = s.supplier_id
        WHERE po.purchase_order_id = ?
    ");

    $detailsStmt->execute([$id]);

    $poDetails = $detailsStmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="container py-4">

    <div class="row justify-content-center">

        <div class="col-lg-6">

            <div class="card shadow-sm border-0">

                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        Receive Purchase Order
                    </h5>
                </div>

                <div class="card-body">

                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($errorMessage) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($poDetails && strtoupper($poDetails['status']) === 'PENDING'): ?>

                        <div class="mb-4">

                            <p class="mb-2">
                                <strong>Purchase Order:</strong>
                                #<?= htmlspecialchars($poDetails['purchase_order_id']) ?>
                            </p>

                            <p class="mb-2">
                                <strong>Supplier:</strong>
                                <?= htmlspecialchars($poDetails['supplier_name']) ?>
                            </p>

                            <p class="mb-0">
                                <strong>Status:</strong>
                                <span class="badge bg-warning text-dark">
                                    <?= htmlspecialchars($poDetails['status']) ?>
                                </span>
                            </p>

                        </div>

                        <div class="alert alert-warning">
                            This action will increase inventory quantities automatically.
                            The operation should only be performed after physical delivery confirmation.
                        </div>

                        <form method="POST">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= Session::getCsrfToken() ?>"
                            >

                            <div class="d-flex justify-content-between">

                                <a
                                    href="<?= url('purchase_orders') ?>"
                                    class="btn btn-secondary"
                                >
                                    Cancel
                                </a>

                                <button
                                    type="submit"
                                    class="btn btn-success"
                                >
                                    Confirm Receipt
                                </button>

                            </div>

                        </form>

                    <?php else: ?>

                        <div class="alert alert-warning">
                            Purchase order not found or already processed.
                        </div>

                        <a
                            href="<?= url('purchase_orders') ?>"
                            class="btn btn-secondary w-100"
                        >
                            Return to Purchase Orders
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>

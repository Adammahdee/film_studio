<?php
/**
 * Request Approval Controller View
 * Location: templates/requests/approve.php
 */

use App\Core\Csrf;
use App\Core\ErrorHandler;
use App\Core\DatabaseTransaction;
use App\Core\AuditLogger;
use App\Security\Session;

// Secure session boot
Session::start();

// Role-based access protection
$role = $_SESSION['role'] ?? '';

if (!in_array($role, ['ADMIN', 'MANAGER'])) {
    ErrorHandler::render403();
    exit();
}

$message = '';
$itemNameColumn = 'name';
$requestDateExpr = 'r.created_at';
try {
    $nameColStmt = $pdo->query("SHOW COLUMNS FROM inventory LIKE 'name'");
    if (!$nameColStmt->fetch(PDO::FETCH_ASSOC)) {
        $itemNameColumn = 'item_name';
    }
    $dateColStmt = $pdo->query("SHOW COLUMNS FROM requests LIKE 'created_at'");
    if (!$dateColStmt->fetch(PDO::FETCH_ASSOC)) {
        $requestDateExpr = 'NULL';
    }
} catch (PDOException $e) {
    $itemNameColumn = 'item_name';
    $requestDateExpr = 'NULL';
}

// ---------------------------------------------------
// Handle Approval / Rejection Actions
// ---------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {

        $message = '
            <div class="alert alert-danger">
                Invalid security token. Please refresh and try again.
            </div>
        ';

    } else {

        $requestId = (int) ($_POST['request_id'] ?? 0);
        $decision  = strtoupper(trim($_POST['decision'] ?? ''));

        if ($requestId > 0 && in_array($decision, ['APPROVE', 'REJECT'])) {

            try {

                DatabaseTransaction::begin();

                // Lock request row during moderation
                $requestSql = sprintf("
                    SELECT 
                        r.request_id,
                        r.item_id,
                        r.quantity,
                        r.status,
                        i.%s AS item_name,
                        i.quantity AS stock_qty
                    FROM requests r
                    INNER JOIN inventory i 
                        ON r.item_id = i.item_id
                    WHERE r.request_id = ?
                    FOR UPDATE
                ", $itemNameColumn);
                $stmt = $pdo->prepare($requestSql);

                $stmt->execute([$requestId]);

                $request = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$request) {
                    throw new Exception('Request record not found.');
                }

                if (strtoupper($request['status']) !== 'PENDING') {
                    throw new Exception('This request has already been processed.');
                }

                // ---------------------------------------------------
                // APPROVE REQUEST
                // ---------------------------------------------------
                if ($decision === 'APPROVE') {

                    $requestedQty = (int) $request['quantity'];
                    $currentStock = (int) $request['stock_qty'];

                    if ($requestedQty > $currentStock) {
                        throw new Exception(
                            'Insufficient stock available for: ' .
                            htmlspecialchars($request['item_name'])
                        );
                    }

                    $newQuantity = $currentStock - $requestedQty;

                    $inventoryStatus = ($newQuantity > 0)
                        ? 'AVAILABLE'
                        : 'OUT_OF_STOCK';

                    // Update inventory
                    $updateInventory = $pdo->prepare("
                        UPDATE inventory
                        SET quantity = ?, status = ?
                        WHERE item_id = ?
                    ");

                    $updateInventory->execute([
                        $newQuantity,
                        $inventoryStatus,
                        $request['item_id']
                    ]);

                    // Update request status
                    $updateRequest = $pdo->prepare("
                        UPDATE requests
                        SET status = 'APPROVED'
                        WHERE request_id = ?
                    ");

                    $updateRequest->execute([$requestId]);

                    // Audit trail
                    if (class_exists(AuditLogger::class)) {
                        AuditLogger::log(
                            'APPROVE_REQUEST',
                            'requests',
                            $requestId,
                            ['status' => 'PENDING'],
                            ['status' => 'APPROVED']
                        );
                    }

                    $message = '
                        <div class="alert alert-success">
                            Request approved successfully.
                            Inventory balance updated.
                        </div>
                    ';

                } else {

                    // ---------------------------------------------------
                    // REJECT REQUEST
                    // ---------------------------------------------------
                    $updateRequest = $pdo->prepare("
                        UPDATE requests
                        SET status = 'REJECTED'
                        WHERE request_id = ?
                    ");

                    $updateRequest->execute([$requestId]);

                    if (class_exists(AuditLogger::class)) {
                        AuditLogger::log(
                            'REJECT_REQUEST',
                            'requests',
                            $requestId,
                            ['status' => 'PENDING'],
                            ['status' => 'REJECTED']
                        );
                    }

                    $message = '
                        <div class="alert alert-warning">
                            Request rejected successfully.
                        </div>
                    ';
                }

                DatabaseTransaction::commit();

            } catch (Exception $e) {

                DatabaseTransaction::rollback();

                $message = '
                    <div class="alert alert-danger">
                        Processing failed: ' .
                        htmlspecialchars($e->getMessage()) .
                    '</div>
                ';
            }
        }
    }
}

// ---------------------------------------------------
// Load Pending Requests
// ---------------------------------------------------
try {

    $pendingSql = sprintf("
        SELECT
            r.request_id,
            r.quantity,
            %s AS created_at,
            COALESCE(u.full_name, 'Unknown User') AS full_name,
            COALESCE(i.%s, 'Unknown Item') AS item_name,
            COALESCE(i.quantity, 0) AS stock_qty
        FROM requests r
        LEFT JOIN users u
            ON r.user_id = u.user_id
        LEFT JOIN inventory i
            ON r.item_id = i.item_id
        WHERE UPPER(r.status) = 'PENDING'
        ORDER BY r.request_id ASC
    ", $requestDateExpr, $itemNameColumn);
    $pendingStmt = $pdo->query($pendingSql);

    $pendingRequests = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log('Pending Request Query Error: ' . $e->getMessage());

    $pendingRequests = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Approve Pending Requests</h2>
        <p class="text-muted mb-0">
            Review and moderate inventory allocation requests.
        </p>
    </div>

    <a href="<?= url('requests') ?>" class="btn btn-outline-secondary">
        View All Requests
    </a>
</div>

<?= $message ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-dark text-white">
        Pending Request Queue
    </div>

    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">
                    <tr>
                        <th>Request ID</th>
                        <th>Requester</th>
                        <th>Inventory Item</th>
                        <th class="text-center">Requested Qty</th>
                        <th class="text-center">Available Stock</th>
                        <th>Date Submitted</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($pendingRequests)): ?>

                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                No pending requests found.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($pendingRequests as $request): ?>

                            <tr>

                                <td>
                                    #<?= htmlspecialchars($request['request_id']) ?>
                                </td>

                                <td class="fw-semibold">
                                    <?= htmlspecialchars($request['full_name']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($request['item_name']) ?>
                                </td>

                                <td class="text-center fw-bold text-primary">
                                    <?= (int) $request['quantity'] ?>
                                </td>

                                <td class="text-center">

                                    <span class="badge <?= ($request['stock_qty'] >= $request['quantity'])
                                        ? 'bg-success'
                                        : 'bg-danger'
                                    ?>">

                                        <?= (int) $request['stock_qty'] ?> available

                                    </span>

                                </td>

                                <td class="text-muted small">
                                    <?= htmlspecialchars((string)($request['created_at'] ?? 'N/A')) ?>
                                </td>

                                <td class="text-end pe-4">

                                    <div class="d-flex justify-content-end gap-2">

                                        <!-- APPROVE -->
                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= Csrf::generateToken() ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="request_id"
                                                value="<?= $request['request_id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="decision"
                                                value="APPROVE"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-success"
                                                <?= ($request['stock_qty'] < $request['quantity'])
                                                    ? 'disabled'
                                                    : ''
                                                ?>
                                            >
                                                Approve
                                            </button>

                                        </form>

                                        <!-- REJECT -->
                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= Csrf::generateToken() ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="request_id"
                                                value="<?= $request['request_id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="decision"
                                                value="REJECT"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                            >
                                                Reject
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>
</div>

<div class="mt-4">
    <a href="<?= url('dashboard') ?>" class="btn btn-secondary">
        Return to Dashboard
    </a>
</div>

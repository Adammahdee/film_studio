<?php
use App\Core\ErrorHandler;
use App\Core\Csrf;

// Access check: All roles can view requests, but STAFF only sees their own
$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? '';

if (!$user_id) {
    ErrorHandler::render403();
    exit();
}

$isAdminOrManager = ($role === 'ADMIN' || $role === 'MANAGER');

try {
    $itemNameColumn = 'name';
    $requestDateExpr = 'r.created_at';
    $nameColStmt = $pdo->query("SHOW COLUMNS FROM inventory LIKE 'name'");
    if (!$nameColStmt->fetch(PDO::FETCH_ASSOC)) {
        $itemNameColumn = 'item_name';
    }
    $dateColStmt = $pdo->query("SHOW COLUMNS FROM requests LIKE 'created_at'");
    if (!$dateColStmt->fetch(PDO::FETCH_ASSOC)) {
        $requestDateExpr = 'NULL';
    }

    // Compile baseline query depending on authorization scope
    if ($isAdminOrManager) {
        // Admins/Managers fetch everything to moderate the full pipeline
        $sql = sprintf("
            SELECT r.request_id, r.quantity, r.status, %s AS created_at, u.full_name, i.%s AS item_name
            FROM requests r
            JOIN users u ON r.user_id = u.user_id
            JOIN inventory i ON r.item_id = i.item_id
            ORDER BY r.request_id DESC
        ", $requestDateExpr, $itemNameColumn);
        $stmt = $pdo->query($sql);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Staff members are strictly bound to their personal submission logs
        $sql = sprintf("
            SELECT r.request_id, r.quantity, r.status, %s AS created_at, u.full_name, i.%s AS item_name
            FROM requests r
            JOIN users u ON r.user_id = u.user_id
            JOIN inventory i ON r.item_id = i.item_id
            WHERE r.user_id = ?
            ORDER BY r.request_id DESC
        ", $requestDateExpr, $itemNameColumn);
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Requests log ledger extraction exception: " . $e->getMessage());
    $requests = [];
}

// Function helper to output uniform badges matching your dashboard style
if (!function_exists('getRequestsBadgeClass')) {
    function getRequestsBadgeClass(string $status): string {
        $status = strtoupper($status);
        if ($status === 'PENDING') return 'bg-warning text-dark';
        if ($status === 'APPROVED') return 'bg-success';
        if ($status === 'REJECTED') return 'bg-danger';
        return 'bg-secondary';
    }
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2>Inventory Fulfillment Requests</h2>
        <p class="text-muted mb-0">
            <?= $isAdminOrManager ? 'Review and manage internal material distribution orders.' : 'Track the structural status of your requested allocations.' ?>
        </p>
    </div>
    <div>
        <a href="<?= url('requests', 'create') ?>" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i> Create New Request
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-striped table-hover table-bordered align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Req ID</th>
                    <th>Requester</th>
                    <th>Item Description</th>
                    <th>Qty Requested</th>
                    <th>Date Lodged</th>
                    <th>Status</th>
                    <?php if ($isAdminOrManager): ?>
                        <th class="text-center">Moderation Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="<?= $isAdminOrManager ? '7' : '6' ?>" class="text-center text-muted py-4">
                            No active asset requests logged in this pipeline view.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td>#<?= htmlspecialchars($r['request_id']) ?></td>
                    <td><?= htmlspecialchars($r['full_name']) ?></td>
                    <td><?= htmlspecialchars($r['item_name']) ?></td>
                    <td><?= (int)$r['quantity'] ?></td>
                    <td><?= htmlspecialchars($r['created_at'] ?? 'N/A') ?></td>
                    <td>
                        <span class="badge <?= getRequestsBadgeClass($r['status']) ?>">
                            <?= htmlspecialchars(strtoupper($r['status'])) ?>
                        </span>
                    </td>
                    <?php if ($isAdminOrManager): ?>
                        <td class="text-center">
                            <?php if (strtoupper($r['status']) === 'PENDING'): ?>
                                <div class="d-flex justify-content-center gap-1">
                                    <form action="<?= url('requests', 'approve') ?>" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= Csrf::generateToken() ?>">
                                        <input type="hidden" name="request_id" value="<?= $r['request_id'] ?>">
                                        <input type="hidden" name="decision" value="APPROVE">
                                        <button type="submit" class="btn btn-sm btn-success py-0 px-2">Approve</button>
                                    </form>
                                    <form action="<?= url('requests', 'approve') ?>" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= Csrf::generateToken() ?>">
                                        <input type="hidden" name="request_id" value="<?= $r['request_id'] ?>">
                                        <input type="hidden" name="decision" value="REJECT">
                                        <button type="submit" class="btn btn-sm btn-danger py-0 px-2">Reject</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small">Processed</span>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <a href="<?= url('dashboard') ?>" class="btn btn-secondary">Back to Dashboard</a>
</div>

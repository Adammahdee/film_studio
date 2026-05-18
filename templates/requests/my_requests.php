<?php
use App\Core\ErrorHandler;

// Security check: Verify that a session identity exists
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    ErrorHandler::render403();
    exit();
}

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

    // Strictly isolate data pull sequence to matching user identity logs
    $sql = sprintf("
        SELECT r.request_id, r.quantity, r.status, %s AS created_at, i.%s AS item_name
        FROM requests r
        JOIN inventory i ON r.item_id = i.item_id
        WHERE r.user_id = ?
        ORDER BY r.request_id DESC
    ", $requestDateExpr, $itemNameColumn);
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $myRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Personal request log stream lookup crash: " . $e->getMessage());
    $myRequests = [];
}

// Function helper to output uniform badges matching your application presentation style
if (!function_exists('getMyRequestsBadgeClass')) {
    function getMyRequestsBadgeClass(string $status): string {
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
        <h2>My Allocation Requests</h2>
        <p class="text-muted mb-0">Track status checkpoints, approval logs, and active warehouse distributions filed under your account.</p>
    </div>
    <div>
        <a href="<?= url('requests', 'create') ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-plus me-1"></i> New Request
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-secondary text-white fw-semibold">
        Personal Submission History Log
    </div>
    <div class="card-body p-0">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Request ID</th>
                    <th>Material Description</th>
                    <th>Units Requested</th>
                    <th>Date Submitted</th>
                    <th>Current Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($myRequests)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            You have not submitted any inventory asset allocation requests yet.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($myRequests as $r): ?>
                <tr>
                    <td><span class="text-mono fw-semibold">#<?= htmlspecialchars($r['request_id']) ?></span></td>
                    <td class="fw-medium text-dark"><?= htmlspecialchars($r['item_name']) ?></td>
                    <td><?= (int)$r['quantity'] ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($r['created_at'] ?? 'N/A') ?></td>
                    <td>
                        <span class="badge <?= getMyRequestsBadgeClass($r['status']) ?>">
                            <?= htmlspecialchars(strtoupper($r['status'])) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <a href="<?= url('dashboard') ?>" class="btn btn-secondary">Return to Dashboard</a>
</div>

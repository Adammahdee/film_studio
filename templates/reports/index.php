<?php

use App\Core\ErrorHandler;

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('auth'));
    exit();
}

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['ADMIN', 'MANAGER'], true)) {
    ErrorHandler::render403();
    exit();
}

$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate   = $_GET['end_date'] ?? date('Y-m-d');

$errorMessage = '';
$poMetrics = [
    'total_pos' => 0,
    'received_pos' => 0,
    'pending_pos' => 0
];
$requestMetrics = [
    'total_requests' => 0,
    'approved_requests' => 0,
    'pending_requests' => 0,
    'rejected_requests' => 0
];
$topDemandedItems = [];

// Purchase order metrics (supports both legacy/current schemas)
$poMetricQueries = [
    "
    SELECT
        COUNT(purchase_order_id) AS total_pos,
        SUM(CASE WHEN UPPER(status) = 'RECEIVED' THEN 1 ELSE 0 END) AS received_pos,
        SUM(CASE WHEN UPPER(status) = 'PENDING' THEN 1 ELSE 0 END) AS pending_pos
    FROM purchase_orders
    WHERE DATE(order_date) BETWEEN ? AND ?
    ",
    "
    SELECT
        COUNT(po_id) AS total_pos,
        SUM(CASE WHEN UPPER(status) = 'RECEIVED' THEN 1 ELSE 0 END) AS received_pos,
        SUM(CASE WHEN UPPER(status) = 'PENDING' THEN 1 ELSE 0 END) AS pending_pos
    FROM purchase_orders
    WHERE DATE(created_at) BETWEEN ? AND ?
    "
];

$poLoaded = false;
foreach ($poMetricQueries as $sql) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        $poMetrics = $stmt->fetch(PDO::FETCH_ASSOC) ?: $poMetrics;
        $poLoaded = true;
        break;
    } catch (PDOException $e) {
        error_log('Reports PO Metrics Error: ' . $e->getMessage());
    }
}
if (!$poLoaded) {
    $errorMessage = 'Unable to load report analytics.';
}

// Request metrics
try {
    $requestMetricsStmt = $pdo->prepare("
        SELECT 
            COUNT(request_id) AS total_requests,
            SUM(CASE WHEN UPPER(status) = 'APPROVED' THEN 1 ELSE 0 END) AS approved_requests,
            SUM(CASE WHEN UPPER(status) = 'PENDING' THEN 1 ELSE 0 END) AS pending_requests,
            SUM(CASE WHEN UPPER(status) = 'REJECTED' THEN 1 ELSE 0 END) AS rejected_requests
        FROM requests
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $requestMetricsStmt->execute([$startDate, $endDate]);
    $requestMetrics = $requestMetricsStmt->fetch(PDO::FETCH_ASSOC) ?: $requestMetrics;
} catch (PDOException $e) {
    error_log('Reports Request Metrics Error: ' . $e->getMessage());
    $errorMessage = 'Unable to load report analytics.';
}

// Top requested items (supports inventory.name and inventory.item_name)
$topItemQueries = [
    "
    SELECT
        i.name AS item_name,
        SUM(r.quantity) AS total_requested_qty,
        COUNT(r.request_id) AS transactional_frequency
    FROM requests r
    INNER JOIN inventory i ON r.item_id = i.item_id
    WHERE UPPER(r.status) = 'APPROVED'
      AND DATE(r.created_at) BETWEEN ? AND ?
    GROUP BY r.item_id, i.name
    ORDER BY total_requested_qty DESC
    LIMIT 5
    ",
    "
    SELECT
        i.item_name AS item_name,
        SUM(r.quantity) AS total_requested_qty,
        COUNT(r.request_id) AS transactional_frequency
    FROM requests r
    INNER JOIN inventory i ON r.item_id = i.item_id
    WHERE UPPER(r.status) = 'APPROVED'
      AND DATE(r.created_at) BETWEEN ? AND ?
    GROUP BY r.item_id, i.item_name
    ORDER BY total_requested_qty DESC
    LIMIT 5
    "
];

$topLoaded = false;
foreach ($topItemQueries as $sql) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        $topDemandedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $topLoaded = true;
        break;
    } catch (PDOException $e) {
        error_log('Reports Top Items Error: ' . $e->getMessage());
    }
}
if (!$topLoaded) {
    $errorMessage = 'Unable to load report analytics.';
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Operational Reports</h2>
        <p class="text-muted mb-0">
            Procurement, inventory, and request analytics overview.
        </p>
    </div>

    <form method="GET" class="card border-0 shadow-sm p-2 bg-light">
        <input type="hidden" name="page" value="reports">
        <div class="d-flex align-items-center gap-2">
            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate) ?>">
            <span class="text-muted">to</span>
            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($endDate) ?>">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        </div>
    </form>
</div>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-warning">
        <?= htmlspecialchars($errorMessage) ?>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <strong>Purchase Orders</strong>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="text-muted small">Total</div>
                        <div class="fs-3 fw-bold"><?= (int)$poMetrics['total_pos'] ?></div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Received</div>
                        <div class="fs-3 fw-bold text-success"><?= (int)$poMetrics['received_pos'] ?></div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Pending</div>
                        <div class="fs-3 fw-bold text-warning"><?= (int)$poMetrics['pending_pos'] ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <strong>Inventory Requests</strong>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-3">
                        <div class="text-muted small">Total</div>
                        <div class="fs-4 fw-bold"><?= (int)$requestMetrics['total_requests'] ?></div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted small">Approved</div>
                        <div class="fs-4 fw-bold text-success"><?= (int)$requestMetrics['approved_requests'] ?></div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted small">Pending</div>
                        <div class="fs-4 fw-bold text-warning"><?= (int)$requestMetrics['pending_requests'] ?></div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted small">Rejected</div>
                        <div class="fs-4 fw-bold text-danger"><?= (int)$requestMetrics['rejected_requests'] ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white">
        <strong>Top Requested Inventory Items</strong>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Item Name</th>
                        <th class="text-center">Request Count</th>
                        <th class="text-end">Total Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topDemandedItems)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                No approved request activity found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($topDemandedItems as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['item_name']) ?></td>
                                <td class="text-center"><?= (int)$item['transactional_frequency'] ?></td>
                                <td class="text-end fw-bold"><?= number_format((float)$item['total_requested_qty']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button onclick="window.print();" class="btn btn-outline-dark">Print Report</button>
    <a href="<?= url('dashboard') ?>" class="btn btn-secondary">Back to Dashboard</a>
</div>

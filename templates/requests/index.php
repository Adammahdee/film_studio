<?php
use App\Auth\Permissions;
require_once ROOT_PATH . 'src/Auth/auth_check.php';
require_once ROOT_PATH . 'config/db.php';

if (!Permissions::hasPermission($_SESSION['role'] ?? '', 'approve_request') && !Permissions::hasPermission($_SESSION['role'] ?? '', 'view_all_requests')) {
    die('<div class="alert alert-danger">Access Denied. You do not have permission to view requests.</div>');
}

require_once ROOT_PATH . "templates/includes/header.php";

$stmt = $conn->query("
    SELECT r.*, u.full_name, i.item_name
    FROM requests r
    JOIN users u ON r.user_id = u.user_id
    JOIN inventory i ON r.item_id = i.item_id
    ORDER BY r.request_id DESC
");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>All Requests</h2>
    <a href="<?= url('requests', 'approve') ?>" class="btn btn-primary">Manage Approvals</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-striped table-hover table-bordered mb-0">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Requester</th>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$requests): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No requests found.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?= $request['request_id'] ?></td>
                        <td><?= htmlspecialchars($request['full_name']) ?></td>
                        <td><?= htmlspecialchars($request['item_name']) ?></td>
                        <td><?= $request['quantity'] ?></td>
                        <td><?= htmlspecialchars($request['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <a href="<?= url('dashboard') ?>" class="btn btn-secondary">Back</a>
</div>

<?php require_once ROOT_PATH . "templates/includes/footer.php"; ?>

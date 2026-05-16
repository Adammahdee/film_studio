<?php
require_once ROOT_PATH . 'src/Auth/auth_check.php';
require_once ROOT_PATH . 'config/db.php';

if ($_SESSION['role'] !== 'STAFF') {
    die("Access denied");
}

require_once __DIR__ . "/../includes/header.php";

$stmt = $conn->prepare("
    SELECT r.*, i.item_name
    FROM requests r
    JOIN inventory i ON r.item_id = i.item_id
    WHERE r.user_id = ?
    ORDER BY r.request_id DESC
");
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>My Requests</h2>
    <a href="/film_studio/requests/create.php" class="btn btn-primary">Create Request</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-striped table-hover table-bordered mb-0">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$requests): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">No requests submitted yet.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?= $request['request_id'] ?></td>
                        <td><?= htmlspecialchars($request['item_name']) ?></td>
                        <td><?= $request['quantity'] ?></td>
                        <td>
                            <?php
                                $badge = 'bg-secondary';
                                if ($request['status'] === 'PENDING') $badge = 'bg-warning text-dark';
                                if ($request['status'] === 'APPROVED') $badge = 'bg-success';
                                if ($request['status'] === 'REJECTED') $badge = 'bg-danger';
                            ?>
                            <span class="badge <?= $badge ?>"><?= htmlspecialchars($request['status']) ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <a href="/film_studio/dashboard.php" class="btn btn-secondary">Back</a>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>

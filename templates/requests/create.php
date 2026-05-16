<?php
require_once ROOT_PATH . 'src/Auth/auth_check.php';
require_once ROOT_PATH . 'config/db.php';

if ($_SESSION['role'] != 'STAFF') {
    die("Access denied");
}

require_once __DIR__ . "/../includes/header.php";

$items = $conn->query("SELECT * FROM inventory WHERE status='AVAILABLE'")->fetchAll();
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$stmt = $conn->prepare("SELECT r.*, i.item_name 
                        FROM requests r
                        JOIN inventory i ON r.item_id = i.item_id
                        WHERE r.user_id = ?
                        ORDER BY r.request_id DESC");
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Create Request</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= url('requests', 'store') ?>">
                    <div class="mb-3">
                        <label class="form-label">Select Item</label>
                        <select name="item_id" class="form-select" required>
                            <option value="">-- Choose --</option>
                            <?php foreach ($items as $item): ?>
                                <option value="<?= $item['item_id'] ?>">
                                    <?= $item['item_name'] ?> (<?= $item['quantity'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" class="form-control" placeholder="0" min="1" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Submit Request</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <h3 class="mb-3">My Requests</h3>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-striped table-hover table-bordered mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $r): ?>
                        <tr>
                            <td><?= $r['request_id'] ?></td>
                            <td><?= $r['item_name'] ?></td>
                            <td><?= $r['quantity'] ?></td>
                            <td>
                                <?php
                                    $badge = 'bg-secondary';
                                    if($r['status'] == 'PENDING') $badge = 'bg-warning text-dark';
                                    if($r['status'] == 'APPROVED') $badge = 'bg-success';
                                    if($r['status'] == 'REJECTED') $badge = 'bg-danger';
                                ?>
                                <span class="badge <?= $badge ?>"><?= $r['status'] ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="<?= url('dashboard') ?>" class="btn btn-secondary">Back</a>
    <a href="<?= url('requests', 'my_requests') ?>" class="btn btn-outline-primary">View My Requests</a>
</div>

<?php require_once ROOT_PATH . "templates/includes/footer.php"; ?>

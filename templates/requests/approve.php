<?php
require_once ROOT_PATH . 'src/Auth/auth_check.php';
require_once ROOT_PATH . 'config/db.php';

if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}

require_once ROOT_PATH . "templates/includes/header.php";

if (isset($_GET['action']) && isset($_GET['id'])) {

    $id = $_GET['id'];
    $action = $_GET['action'];

    $stmt = $conn->prepare("SELECT * FROM requests WHERE request_id=?");
    $stmt->execute([$id]);
    $req = $stmt->fetch();

    if ($req && $req['status'] == 'PENDING') {

        if ($action == "approve") {

            $stmt = $conn->prepare("SELECT * FROM inventory WHERE item_id=?");
            $stmt->execute([$req['item_id']]);
            $item = $stmt->fetch();

            if ($item['quantity'] >= $req['quantity']) {

                $newQty = $item['quantity'] - $req['quantity'];

                if ($newQty < 0) {
                    die("Stock error");
                }

                $status = ($newQty > 0) ? 'AVAILABLE' : 'OUT_OF_STOCK';

                $conn->prepare("UPDATE inventory SET quantity=?, status=? WHERE item_id=?")
                     ->execute([$newQty, $status, $req['item_id']]);

                $conn->prepare("UPDATE requests SET status='APPROVED' WHERE request_id=?")
                     ->execute([$id]);

            } else {
                echo '<div class="alert alert-danger">Not enough stock</div>';
            }

        } elseif ($action == "reject") {

            $conn->prepare("UPDATE requests SET status='REJECTED' WHERE request_id=?")
                 ->execute([$id]);
        }
    }
}

$stmt = $conn->query("SELECT r.*, u.full_name, i.item_name
                      FROM requests r
                      JOIN users u ON r.user_id = u.user_id
                      JOIN inventory i ON r.item_id = i.item_id
                      ORDER BY r.request_id DESC");

$requests = $stmt->fetchAll();
?>

<h2 class="mb-4">Approval System</h2>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-striped table-hover table-bordered mb-0">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td><?= $r['request_id'] ?></td>
                    <td><?= $r['full_name'] ?></td>
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
                    <td>
                        <?php if ($r['status'] == 'PENDING'): ?>
                            <a href="<?= url('requests', 'approve', ['action_type' => 'approve', 'id' => $r['request_id']]) ?>" class="btn btn-success btn-sm">Approve</a>
                            <a href="<?= url('requests', 'approve', ['action_type' => 'reject', 'id' => $r['request_id']]) ?>" class="btn btn-danger btn-sm">Reject</a>
                        <?php else: ?>
                            <span class="text-muted">Done</span>
                        <?php endif; ?>
                    </td>
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

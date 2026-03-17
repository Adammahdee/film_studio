<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}

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
                echo "Not enough stock<br>";
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

<h2>Approval System</h2>

<table border="1">
<tr>
    <th>ID</th>
    <th>User</th>
    <th>Item</th>
    <th>Qty</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php foreach ($requests as $r): ?>
<tr>
    <td><?= $r['request_id'] ?></td>
    <td><?= $r['full_name'] ?></td>
    <td><?= $r['item_name'] ?></td>
    <td><?= $r['quantity'] ?></td>
    <td><?= $r['status'] ?></td>
    <td>
        <?php if ($r['status'] == 'PENDING'): ?>
            <a href="?action=approve&id=<?= $r['request_id'] ?>">Approve</a> |
            <a href="?action=reject&id=<?= $r['request_id'] ?>">Reject</a>
        <?php else: ?>
            Done
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>

<br>
<a href="/film_studio/index.php">Back</a>
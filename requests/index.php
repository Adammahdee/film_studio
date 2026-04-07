<?php
require_once "../config/db.php";

$stmt = $conn->query("
    SELECT r.*, i.item_name
    FROM requests r
    JOIN inventory i ON r.inventory_id = i.item_id
    ORDER BY r.created_at DESC
");

$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>All Requests</h2>

<table border="1">
<tr>
    <th>ID</th>
    <th>Item</th>
    <th>Qty</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php foreach ($requests as $r): ?>
<tr>
    <td><?= $r['request_id'] ?></td>
    <td><?= htmlspecialchars($r['item_name']) ?></td>
    <td><?= $r['quantity'] ?></td>
    <td><?= $r['status'] ?></td>
    <td>
        <?php if ($r['status'] === 'PENDING'): ?>
            <a href="approve.php?id=<?= $r['request_id'] ?>">Approve</a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
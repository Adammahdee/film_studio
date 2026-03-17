<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}

$stmt = $conn->query("SELECT * FROM inventory ORDER BY item_id DESC");
$items = $stmt->fetchAll();
?>

<h2>Inventory</h2>

<a href="/film_studio/inventory/add_item.php">Add New Item</a><br><br>

<table border="1">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Quantity</th>
    <th>Status</th>
</tr>

<?php foreach ($items as $item): ?>
<tr>
    <td><?= $item['item_id'] ?></td>
    <td><?= $item['item_name'] ?></td>
    <td><?= $item['quantity'] ?></td>
    <td><?= $item['status'] ?></td>
</tr>
<?php endforeach; ?>
</table>

<br>
<a href="/film_studio/index.php">Back</a>
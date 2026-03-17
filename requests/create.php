<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

if ($_SESSION['role'] != 'STAFF') {
    die("Access denied");
}

$items = $conn->query("SELECT * FROM inventory WHERE status='AVAILABLE'")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $item_id = $_POST['item_id'];
    $qty = $_POST['quantity'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE item_id=?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();

    if ($qty <= 0) {
        echo "Invalid quantity<br>";
    } elseif (!$item) {
        echo "Item not found<br>";
    } elseif ($qty > $item['quantity']) {
        echo "Exceeds stock<br>";
    } else {
        $stmt = $conn->prepare("INSERT INTO requests (user_id, item_id, quantity)
                                VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $item_id, $qty]);

        echo "Request submitted<br>";
    }
}

$stmt = $conn->prepare("SELECT r.*, i.item_name 
                        FROM requests r
                        JOIN inventory i ON r.item_id = i.item_id
                        WHERE r.user_id = ?
                        ORDER BY r.request_id DESC");
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll();
?>

<h2>Request Item</h2>

<form method="POST">
    <select name="item_id" required>
        <option value="">Select Item</option>
        <?php foreach ($items as $item): ?>
            <option value="<?= $item['item_id'] ?>">
                <?= $item['item_name'] ?> (<?= $item['quantity'] ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <input type="number" name="quantity" placeholder="Quantity" required>
    <button type="submit">Request</button>
</form>

<br>

<h3>My Requests</h3>

<table border="1">
<tr>
    <th>ID</th>
    <th>Item</th>
    <th>Qty</th>
    <th>Status</th>
</tr>

<?php foreach ($requests as $r): ?>
<tr>
    <td><?= $r['request_id'] ?></td>
    <td><?= $r['item_name'] ?></td>
    <td><?= $r['quantity'] ?></td>
    <td><?= $r['status'] ?></td>
</tr>
<?php endforeach; ?>
</table>

<br>
<a href="/film_studio/index.php">Back</a>
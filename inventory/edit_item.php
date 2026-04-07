<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}

require_once __DIR__ . "/../includes/header.php";

$id = (int) $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM inventory WHERE item_id=?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) die("Item not found");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST['item_name'];
    $qty = (int) $_POST['quantity'];

    $status = ($qty > 0) ? 'AVAILABLE' : 'OUT_OF_STOCK';

    $conn->prepare("UPDATE inventory SET item_name=?, quantity=?, status=? WHERE item_id=?")
         ->execute([$name, $qty, $status, $id]);

    header("Location: /film_studio/inventory/index.php");
    exit();
}
?>

<h2 class="mb-4">Edit Inventory Item</h2>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0">Update Item Details</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Item Name</label>
                        <input type="text" name="item_name" class="form-control" value="<?= htmlspecialchars($item['item_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" class="form-control" value="<?= $item['quantity'] ?>" min="0" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Item</button>
                    <a href="/film_studio/inventory/index.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
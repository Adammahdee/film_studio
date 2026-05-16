<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

// ONLY ADMIN OR MANAGER
if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}

require_once __DIR__ . "/../includes/header.php";

// HANDLE FORM
$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['item_name']);
    $qty = $_POST['quantity'];

    if ($name == "") {
        echo "Item name required<br>";
    } elseif ($qty < 0) {
        echo "Quantity cannot be negative<br>";
    } else {

        $status = ($qty > 0) ? 'AVAILABLE' : 'OUT_OF_STOCK';

        $stmt = $conn->prepare("INSERT INTO inventory (item_name, quantity, status)
                                VALUES (?, ?, ?)");
        $stmt->execute([$name, $qty, $status]);
        $msg = '<div class="alert alert-success">Item added successfully</div>';
    }
}
?>

<h2 class="mb-4">Add Inventory Item</h2>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <?= $msg ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Item Name</label>
                        <input type="text" name="item_name" class="form-control" placeholder="e.g. Camera Tripod" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Initial Quantity</label>
                        <input type="number" name="quantity" class="form-control" placeholder="0" min="0" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                    <a href="index.php" class="btn btn-secondary">Back to Inventory</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
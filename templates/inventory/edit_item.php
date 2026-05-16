<?php
use App\Core\Csrf;
require_once ROOT_PATH . "src/Auth/auth_check.php";
require_once ROOT_PATH . 'config/db.php';

if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}
// This will be replaced by Permissions::hasPermission later

require_once ROOT_PATH . "templates/includes/header.php";

$id = (int) $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM inventory WHERE item_id=?");
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) die("Item not found");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        // For now, just die. Later, use ErrorHandler::render403()
        die("Invalid CSRF token. Please try again.");
    } else {
        $name = $_POST['item_name'];
        $qty = (int) $_POST['quantity'];
    
        $status = ($qty > 0) ? 'AVAILABLE' : 'OUT_OF_STOCK';
    
        $conn->prepare("UPDATE inventory SET item_name=?, quantity=?, status=? WHERE item_id=?")
             ->execute([$name, $qty, $status, $id]);
    
        header("Location: " . url('inventory'));
        exit();
    }
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
                <?= $msg ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Csrf::generateToken() ?>">
                    <div class="mb-3">
                        <label class="form-label">Item Name</label>
                        <input type="text" name="item_name" class="form-control <?= isset($errors['item_name']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($item['item_name']) ?>" required>
                        <?php if (isset($errors['item_name'])): ?><div class="invalid-feedback"><?= $errors['item_name'] ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" class="form-control <?= isset($errors['quantity']) ? 'is-invalid' : '' ?>" value="<?= $item['quantity'] ?>" min="0" required>
                        <?php if (isset($errors['quantity'])): ?><div class="invalid-feedback"><?= $errors['quantity'] ?></div><?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Item</button>
                    <a href="/film_studio/inventory/index.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . "templates/includes/footer.php"; ?>
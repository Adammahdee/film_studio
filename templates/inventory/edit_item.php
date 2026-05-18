<?php

use App\Core\Csrf;
use App\Core\ErrorHandler;
use App\Core\AuditLogger;

// Role guard
$role = $_SESSION['role'] ?? '';
if ($role !== 'ADMIN' && $role !== 'MANAGER') {
    ErrorHandler::render403();
    exit();
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['errors'] = "Invalid inventory item.";
    header("Location: " . url('inventory'));
    exit();
}

$msg = "";

// Load item (pre-controller layer)
try {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE item_id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        $_SESSION['errors'] = "Inventory item not found.";
        header("Location: " . url('inventory'));
        exit();
    }

} catch (PDOException $e) {
    error_log("Inventory fetch failed: " . $e->getMessage());
    $_SESSION['errors'] = "System error while loading item.";
    header("Location: " . url('inventory'));
    exit();
}

// Update handler (controller layer)
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        error_log("CSRF failure on inventory update. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
        ErrorHandler::render403();
        exit();
    }

    $name = trim($_POST['item_name'] ?? '');
    $qty = (int)($_POST['quantity'] ?? 0);

    if ($name === '') {
        $msg = "Item name is required.";
    } elseif ($qty < 0) {
        $msg = "Quantity cannot be negative.";
    } else {

        try {
            $status = ($qty > 0) ? 'AVAILABLE' : 'OUT_OF_STOCK';

            $oldData = [
                'name' => $item['name'],
                'quantity' => (int)$item['quantity'],
                'status' => $item['status']
            ];

            $newData = [
                'name' => $name,
                'quantity' => $qty,
                'status' => $status
            ];

            $stmt = $pdo->prepare("
                UPDATE inventory
                SET name = ?, quantity = ?, status = ?
                WHERE item_id = ?
            ");

            $stmt->execute([$name, $qty, $status, $id]);

            AuditLogger::log(
                'UPDATE_INVENTORY',
                'inventory',
                $id,
                $oldData,
                $newData
            );

            $_SESSION['success_msg'] = "Inventory updated successfully.";

            header("Location: " . url('inventory'));
            exit();

        } catch (PDOException $e) {
            error_log("Inventory update failed: " . $e->getMessage());
            $msg = "System error occurred while updating item.";
        }
    }
}
?>

<!-- VIEW LAYER ONLY -->
<div class="container py-4">

    <div class="row mb-3">
        <div class="col">
            <h4>Edit Inventory Item</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">

            <div class="card shadow-sm">

                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Update Item</h5>
                </div>

                <div class="card-body">

                    <?php if ($msg): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($msg) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Csrf::generateToken() ?>">

                        <div class="mb-3">
                            <label>Item Name</label>
                            <input type="text" name="item_name"
                                   class="form-control"
                                   value="<?= htmlspecialchars($item['name'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label>Quantity</label>
                            <input type="number" name="quantity"
                                   class="form-control"
                                   value="<?= (int)($item['quantity'] ?? 0) ?>"
                                   min="0"
                                   required>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="<?= url('inventory') ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>

                    </form>

                </div>

            </div>

        </div>
    </div>

</div>

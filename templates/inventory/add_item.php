<?php
use App\Core\Csrf;
use App\Core\ErrorHandler;

// Role-based privilege validation check
$role = $_SESSION['role'] ?? '';
if ($role !== 'ADMIN' && $role !== 'MANAGER') {
    ErrorHandler::render403();
    exit();
}

$msg = "";

// Process Item Creation Request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        $msg = '<div class="alert alert-danger">Invalid CSRF token. Please try again.</div>';
        error_log("CSRF attack detected on add item form from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
    } else {
        $name = trim($_POST['item_name'] ?? '');
        $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

        if ($name === "") {
            $msg = '<div class="alert alert-danger">Item name required.</div>';
        } elseif ($qty < 0) {
            $msg = '<div class="alert alert-danger">Quantity cannot be negative.</div>';
        } else {
            try {
                $status = ($qty > 0) ? 'AVAILABLE' : 'OUT_OF_STOCK';

                // Support both schemas: inventory.name and inventory.item_name
                $nameColumn = 'name';
                $colStmt = $pdo->query("SHOW COLUMNS FROM inventory LIKE 'name'");
                if (!$colStmt->fetch(PDO::FETCH_ASSOC)) {
                    $nameColumn = 'item_name';
                }

                $sql = sprintf(
                    "INSERT INTO inventory (%s, quantity, status) VALUES (?, ?, ?)",
                    $nameColumn
                );
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $qty, $status]);
                
                $msg = '<div class="alert alert-success">Item added successfully.</div>';
            } catch (PDOException $e) {
                error_log("Inventory insertion failure: " . $e->getMessage());
                $msg = '<div class="alert alert-danger">Error saving item to database logs.</div>';
            }
        }
    }
}
?>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Add Inventory Item</h5>
            </div>
            <div class="card-body">
                <?= $msg ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Csrf::generateToken() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Item Name</label>
                        <input type="text" name="item_name" class="form-control" placeholder="e.g. Camera Tripod" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Initial Quantity</label>
                        <input type="number" name="quantity" class="form-control" placeholder="0" min="0" required>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Add Item</button>
                        <a href="<?= url('inventory') ?>" class="btn btn-secondary">Back to Inventory</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

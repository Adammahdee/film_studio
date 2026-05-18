<?php
use App\Core\Csrf;
use App\Core\ErrorHandler;

// Security core check: Send unauthorized traffic back to sign-in checkpoint
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    ErrorHandler::render403();
    exit();
}

// Pre-fetch available, in-stock products to dynamically bind selection elements 
try {
    // Support both schemas: inventory.name and inventory.item_name
    $nameColumn = 'name';
    $nameCheck = $pdo->query("SHOW COLUMNS FROM inventory LIKE 'name'");
    if (!$nameCheck->fetch(PDO::FETCH_ASSOC)) {
        $nameColumn = 'item_name';
    }

    // Primary filter is positive quantity; allow legacy status drift.
    $sql = sprintf("
        SELECT item_id, %s AS item_name, quantity 
        FROM inventory 
        WHERE quantity > 0
          AND (status = 'AVAILABLE' OR status IS NULL OR status = '')
        ORDER BY %s ASC
    ", $nameColumn, $nameColumn);

    $inventoryItems = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // Fallback: if status values are inconsistent, still show positive stock.
    if (empty($inventoryItems)) {
        $fallbackSql = sprintf("
            SELECT item_id, %s AS item_name, quantity
            FROM inventory
            WHERE quantity > 0
            ORDER BY %s ASC
        ", $nameColumn, $nameColumn);
        $inventoryItems = $pdo->query($fallbackSql)->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Inventory item selection compilation crash: " . $e->getMessage());
    $inventoryItems = [];
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0"><i class="bi bi-file-earmark-plus"></i> Submit Material Request</h5>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['errors'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['errors']); unset($_SESSION['errors']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success_msg'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= url('requests', 'store') ?>">
                    <input type="hidden" name="csrf_token" value="<?= Csrf::generateToken() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">Select Material/Equipment</label>
                        <select name="item_id" class="form-select" required>
                            <option value="" selected disabled>-- Choose available warehouse asset --</option>
                            <?php foreach ($inventoryItems as $item): ?>
                                <option value="<?= $item['item_id'] ?>">
                                    <?= htmlspecialchars($item['item_name']) ?> (<?= (int)$item['quantity'] ?> left)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">Requested Quantity</label>
                        <input type="number" name="quantity" class="form-control" placeholder="e.g. 1" min="1" required>
                        <div class="form-text">Ensure requested value does not exceed available volume pools noted above.</div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="<?= url('requests') ?>" class="btn btn-secondary">Cancel & Back</a>
                        <button type="submit" class="btn btn-success">Lodge Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

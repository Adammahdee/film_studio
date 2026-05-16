<?php
// templates/purchase_orders/create.php

// 1. Resolve dependencies safely using absolute ROOT_PATH
require_once ROOT_PATH . 'src/Auth/Auth_check.php';
require_once ROOT_PATH . 'config/db.php';

// Role access security check
if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}

// 2. Fetch data using the centralized $pdo instance (not $conn) as raw arrays
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$items = $pdo->query("SELECT * FROM inventory ORDER BY item_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Create Purchase Order</h2>
    <a href="<?= url('purchase_orders'); ?>" class="btn btn-secondary">Back</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?= url('purchase_orders'); ?>&action=store">
            
            <div class="mb-4">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-select" required>
                    <option value="">-- Select Supplier --</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['supplier_id'] ?>">
                            <?= htmlspecialchars($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <h5 class="mb-3">Order Items</h5>
            <div id="items-container">
                <div class="row g-3 mb-2 item-row">
                    <div class="col-md-6">
                        <select name="inventory_id[]" class="form-select" required>
                            <option value="">-- Select Item --</option>
                            <?php foreach ($items as $i): ?>
                                <option value="<?= $i['item_id'] ?>">
                                    <?= htmlspecialchars($i['item_name']) ?> (Stock: <?= $i['quantity'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="quantity[]" class="form-control" placeholder="Qty" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="price[]" class="form-control" placeholder="Unit Price" min="0" step="0.01" required>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="add-row">
                    + Add Another Item
                </button>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" name="save_mode" value="pending" class="btn btn-success">Create Purchase Order</button>
                <button type="submit" name="save_mode" value="received" class="btn btn-outline-success">Record Purchase</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('add-row').addEventListener('click', function() {
    const container = document.getElementById('items-container');
    const row = container.querySelector('.item-row').cloneNode(true);
    
    // Reset values smoothly
    row.querySelectorAll('input').forEach(input => input.value = '');
    row.querySelector('select').value = '';
    
    container.appendChild(row);
});
</script>
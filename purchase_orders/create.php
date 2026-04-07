<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}

require_once __DIR__ . "/../includes/header.php";

$suppliers = $conn->query("SELECT * FROM suppliers ORDER BY name ASC")->fetchAll();
$items = $conn->query("SELECT * FROM inventory ORDER BY item_name ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Create Purchase Order</h2>
    <a href="index.php" class="btn btn-secondary">Back</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="store.php">
            
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

            <button type="submit" class="btn btn-success">Create Order</button>
        </form>
    </div>
</div>

<script>
document.getElementById('add-row').addEventListener('click', function() {
    const container = document.getElementById('items-container');
    const row = container.querySelector('.item-row').cloneNode(true);
    
    // Reset values
    row.querySelectorAll('input').forEach(input => input.value = '');
    row.querySelector('select').value = '';
    
    container.appendChild(row);
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
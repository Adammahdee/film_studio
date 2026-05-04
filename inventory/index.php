<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}

require_once __DIR__ . "/../includes/header.php";

$where = [];
$params = [];

if (!empty($_GET['search'])) {
    $where[] = "item_name LIKE ?";
    $params[] = "%" . $_GET['search'] . "%";
}

if (!empty($_GET['status'])) {
    $where[] = "status = ?";
    $params[] = $_GET['status'];
}

$sql = "SELECT * FROM inventory";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY item_id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Inventory</h2>
    <form method="GET" class="mb-3">

    <input type="text" name="search" placeholder="Search item"
           value="<?= $_GET['search'] ?? '' ?>">

    <select name="status">
        <option value="">All</option>
        <option value="AVAILABLE">Available</option>
        <option value="OUT_OF_STOCK">Out of Stock</option>
    </select>

    <button>Filter</button>
</form>
    <a href="/film_studio/inventory/add_item.php" class="btn btn-success">Add New Item</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-striped table-hover table-bordered mb-0">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= $item['item_id'] ?></td>
                    <td><?= $item['item_name'] ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>
                        <?php if ($item['status'] == 'AVAILABLE'): ?>
                            <span class="badge bg-success">AVAILABLE</span>
                        <?php else: ?>
                            <span class="badge bg-danger">OUT_OF_STOCK</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="/film_studio/inventory/edit_item.php?id=<?= $item['item_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="/film_studio/inventory/delete_item.php?id=<?= $item['item_id'] ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Delete this item?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <a href="/film_studio/dashboard.php" class="btn btn-secondary">Back</a>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>

<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once ROOT_PATH . 'config/db.php';

// ROLE CHECK
if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}

require_once __DIR__ . "/../includes/header.php";

// HANDLE FORM SUBMISSION
$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // BASIC VALIDATION
    if ($name && $contact_person && $phone) {

        $stmt = $conn->prepare("
            INSERT INTO suppliers (name, contact_person, phone, email)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([$name, $contact_person, $phone, $email]);
        $msg = '<div class="alert alert-success">Supplier added successfully</div>';

    } else {
        $msg = '<div class="alert alert-danger">All required fields must be filled.</div>';
    }
}

// FETCH SUPPLIERS
$suppliers = $conn->query("
    SELECT * FROM suppliers
    ORDER BY supplier_id DESC
")->fetchAll();
?>

<h2 class="mb-4">Suppliers</h2>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">Add New Supplier</div>
    <div class="card-body">
        <?= $msg ?>
        <form method="POST" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="name" class="form-control" placeholder="Supplier Name" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="contact_person" class="form-control" placeholder="Contact Person" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="phone" class="form-control" placeholder="Phone" required>
            </div>
            <div class="col-md-3">
                <input type="email" name="email" class="form-control" placeholder="Email">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-success">Add Supplier</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <table class="table table-striped table-hover table-bordered mb-0">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Contact Person</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($suppliers as $s): ?>
            <tr>
                <td><?= $s['supplier_id'] ?></td>
                <td><?= htmlspecialchars($s['name'] ?? '') ?></td>
                <td><?= htmlspecialchars($s['contact_person'] ?? '') ?></td>
                <td><?= htmlspecialchars($s['phone'] ?? '') ?></td>
                <td><?= htmlspecialchars($s['email'] ?? '') ?></td>
                <td>
                    <a href="edit.php?id=<?= $s['supplier_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="delete.php?id=<?= $s['supplier_id'] ?>" 
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Are you sure you want to delete this supplier?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="mt-3">
    <a href="/film_studio/dashboard.php" class="btn btn-secondary">Back</a>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>

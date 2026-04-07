<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}

require_once __DIR__ . "/../includes/header.php";

$id = $_GET['id'] ?? 0;
$msg = "";

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $contact = trim($_POST['contact_person']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);

    if ($name && $contact && $phone) {
        $stmt = $conn->prepare("UPDATE suppliers SET name=?, contact_person=?, phone=?, email=? WHERE supplier_id=?");
        $stmt->execute([$name, $contact, $phone, $email, $id]);
        $msg = '<div class="alert alert-success">Supplier updated successfully.</div>';
    } else {
        $msg = '<div class="alert alert-danger">All required fields must be filled.</div>';
    }
}

// Fetch Supplier
$stmt = $conn->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
$stmt->execute([$id]);
$s = $stmt->fetch();

if (!$s) {
    die("Supplier not found");
}
?>

<h2 class="mb-4">Edit Supplier</h2>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0">Edit Supplier Details</h5>
            </div>
            <div class="card-body">
                <?= $msg ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Supplier Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($s['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control" value="<?= htmlspecialchars($s['contact_person']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($s['phone']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($s['email']) ?>">
                    </div>
                    <button type="submit" class="btn btn-success w-100">Update Supplier</button>
                    <a href="index.php" class="btn btn-secondary w-100 mt-2">Back to List</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
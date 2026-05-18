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
    $_SESSION['errors'] = "Invalid supplier ID.";
    header("Location: " . url('suppliers'));
    exit();
}

$msg = "";

// Load supplier (pre-controller data layer)
try {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
    $stmt->execute([$id]);
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$supplier) {
        $_SESSION['errors'] = "Supplier not found.";
        header("Location: " . url('suppliers'));
        exit();
    }

} catch (PDOException $e) {
    error_log("Supplier fetch failed: " . $e->getMessage());
    $_SESSION['errors'] = "System error while loading supplier.";
    header("Location: " . url('suppliers'));
    exit();
}

// POST update handler (controller layer)
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        error_log("CSRF failure on supplier update. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
        ErrorHandler::render403();
        exit();
    }

    $name = trim($_POST['name'] ?? '');
    $contactName = trim($_POST['contact_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '' || $email === '') {
        $msg = "Company name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Invalid email format.";
    } else {

        try {
            $oldData = [
                'name' => $supplier['name'],
                'contact_name' => $supplier['contact_name'] ?? null,
                'email' => $supplier['email'],
                'phone' => $supplier['phone'] ?? null
            ];

            $newData = [
                'name' => $name,
                'contact_name' => $contactName,
                'email' => $email,
                'phone' => $phone
            ];

            $stmt = $pdo->prepare("
                UPDATE suppliers
                SET name = ?, contact_name = ?, email = ?, phone = ?
                WHERE supplier_id = ?
            ");

            $stmt->execute([$name, $contactName, $email, $phone, $id]);

            AuditLogger::log(
                'UPDATE_SUPPLIER',
                'suppliers',
                $id,
                $oldData,
                $newData
            );

            $_SESSION['success_msg'] = "Supplier updated successfully.";

            header("Location: " . url('suppliers'));
            exit();

        } catch (PDOException $e) {
            error_log("Supplier update failed: " . $e->getMessage());
            $msg = "Database error occurred while updating supplier.";
        }
    }
}
?>

<!-- VIEW LAYER ONLY -->
<div class="container py-4">

    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card shadow-sm">

                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Edit Supplier</h5>
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
                            <label>Company Name</label>
                            <input type="text" name="name"
                                   class="form-control"
                                   value="<?= htmlspecialchars($supplier['name'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label>Contact Person</label>
                            <input type="text" name="contact_name"
                                   class="form-control"
                                   value="<?= htmlspecialchars($supplier['contact_name'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email"
                                   class="form-control"
                                   value="<?= htmlspecialchars($supplier['email'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label>Phone</label>
                            <input type="text" name="phone"
                                   class="form-control"
                                   value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>">
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?= url('suppliers') ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>

</div>

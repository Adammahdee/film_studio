<?php

use App\Core\Csrf;
use App\Core\ErrorHandler;
use App\Core\AuditLogger;

// Auth / role guard
$role = $_SESSION['role'] ?? '';
if ($role !== 'ADMIN' && $role !== 'MANAGER') {
    ErrorHandler::render403();
    exit();
}

$msg = "";

// Handle POST request only (controller layer)
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        error_log("CSRF violation on supplier creation. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
        $msg = "Invalid request token.";
    } else {

        $name = trim($_POST['name'] ?? '');
        $contactName = trim($_POST['contact_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        // Validation
        if ($name === '' || $email === '') {
            $msg = "Company name and email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = "Invalid email format.";
        } else {

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO suppliers (name, contact_name, email, phone)
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->execute([$name, $contactName, $email, $phone]);
                $supplierId = $pdo->lastInsertId();

                AuditLogger::log(
                    'CREATE_SUPPLIER',
                    'suppliers',
                    $supplierId,
                    null,
                    [
                        'name' => $name,
                        'contact_name' => $contactName,
                        'email' => $email,
                        'phone' => $phone
                    ]
                );

                $_SESSION['success_msg'] = "Supplier registered successfully.";

                header("Location: " . url('suppliers'));
                exit();

            } catch (PDOException $e) {
                error_log("Supplier insert failed: " . $e->getMessage());
                $msg = "System error occurred while saving supplier.";
            }
        }
    }
}
?>

<!-- VIEW LAYER START -->
<div class="container py-4">

    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card shadow-sm">

                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Register Supplier</h5>
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
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Contact Person</label>
                            <input type="text" name="contact_name" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?= url('suppliers') ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success">Save</button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>

</div>

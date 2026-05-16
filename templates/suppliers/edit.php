<?php
use App\Core\Csrf;
use App\Core\DatabaseTransaction;
use App\Core\ErrorHandler;
use App\Services\AuditLogger;

require_once ROOT_PATH . 'src/Auth/auth_check.php';
require_once ROOT_PATH . 'config/db.php';

if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    ErrorHandler::render403("You do not have permission to edit suppliers.");
}

require_once ROOT_PATH . "templates/includes/header.php";

$id = $_GET['id'] ?? 0;
$msg = "";

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        $msg = '<div class="alert alert-danger">Invalid CSRF token. Please try again.</div>';
        error_log("CSRF attack detected on edit supplier form from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
    } else {
        $name = trim($_POST['name']);
        $contact = trim($_POST['contact_person']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);

        if ($name && $contact && $phone) {
            try {
                DatabaseTransaction::begin();
                
                // Fetch current state for logging before update
                $currentStmt = $conn->prepare("SELECT name, contact_person, phone, email FROM suppliers WHERE supplier_id = ?");
                $currentStmt->execute([$id]);
                $oldData = $currentStmt->fetch(PDO::FETCH_ASSOC);

                $stmt = $conn->prepare("UPDATE suppliers SET name=?, contact_person=?, phone=?, email=? WHERE supplier_id=?");
                $stmt->execute([$name, $contact, $phone, $email, $id]);

                AuditLogger::log('UPDATE', 'Suppliers', (int)$id, $oldData, ['name' => $name, 'contact_person' => $contact, 'phone' => $phone, 'email' => $email]);
                DatabaseTransaction::commit();
                $msg = '<div class="alert alert-success">Supplier updated successfully.</div>';
            } catch (PDOException $e) {
                DatabaseTransaction::rollback();
                $msg = '<div class="alert alert-danger">Error updating supplier: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        } else {
            $msg = '<div class="alert alert-danger">All required fields must be filled.</div>';
        }
    }
}

// Fetch Supplier
$stmt = $conn->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
$stmt->execute([$id]);
$s = $stmt->fetch();

if (!$s) {
    ErrorHandler::render404("Supplier not found.");
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
                    <input type="hidden" name="csrf_token" value="<?= Csrf::generateToken() ?>">
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
                    <a href="<?= url('suppliers') ?>" class="btn btn-secondary w-100 mt-2">Back to List</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . "templates/includes/footer.php"; ?>
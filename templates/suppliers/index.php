<?php
use App\Core\ErrorHandler;
use App\Core\Csrf;

// Access control layer: Restricted to corporate administrative and logistics personnel
$role = $_SESSION['role'] ?? '';
if ($role !== 'ADMIN' && $role !== 'MANAGER') {
    ErrorHandler::render403();
    exit();
}

try {
    // Extract supplier entities alongside calculated aggregate total of past purchase order fills
    $stmt = $pdo->query("
        SELECT s.*, 
        (SELECT COUNT(*) FROM purchase_orders po WHERE po.supplier_id = s.supplier_id) AS total_orders
        FROM suppliers s
        ORDER BY s.name ASC
    ");
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Supplier index repository query exception: " . $e->getMessage());
    $suppliers = [];
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2>External Suppliers & Vendors</h2>
        <p class="text-muted mb-0">Manage global sourcing channels, procurement links, and manufacturing contracts.</p>
    </div>
    <div>
        <a href="<?= url('suppliers', 'create') ?>" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Register New Supplier
        </a>
    </div>
</div>

<?php if (isset($_SESSION['success_msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Company Name</th>
                    <th>Contact Person</th>
                    <th>Email Address</th>
                    <th>Phone Line</th>
                    <th class="text-center">Procurements Logged</th>
                    <th class="text-end px-4">Management Controls</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            No registered external suppliers or production vendors located.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td><span class="text-mono fw-semibold">#<?= htmlspecialchars($s['supplier_id']) ?></span></td>
                    <td class="fw-bold text-dark"><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['contact_name'] ?? $s['contact_person'] ?? 'N/A') ?></td>
                    <td>
                        <a href="mailto:<?= htmlspecialchars($s['email']) ?>" class="text-decoration-none">
                            <?= htmlspecialchars($s['email']) ?>
                        </a>
                    </td>
                    <td><span class="text-nowrap"><?= htmlspecialchars($s['phone'] ?? 'N/A') ?></span></td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border">
                            <?= (int)$s['total_orders'] ?> orders
                        </span>
                    </td>
                    <td class="text-end px-4">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="<?= url('suppliers', 'edit', ['id' => $s['supplier_id']]) ?>" class="btn btn-sm btn-outline-secondary py-1">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            
                            <form action="<?= url('suppliers', 'delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to drop this supplier profile from the active tracking index?');">
                                <input type="hidden" name="csrf_token" value="<?= Csrf::generateToken() ?>">
                                <input type="hidden" name="id" value="<?= $s['supplier_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger py-1">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <a href="<?= url('dashboard') ?>" class="btn btn-secondary">Return to Live Overview Terminal</a>
</div>
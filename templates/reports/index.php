<?php
use App\Auth\Permissions;
require_once ROOT_PATH . 'config/db.php';

if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}
// Use Permissions helper for access control
if (!Permissions::hasPermission($_SESSION['role'] ?? '', 'view_reports')) {
    die('<div class="alert alert-danger">Access Denied. You do not have permission to view reports.</div>');
}

require_once ROOT_PATH . "templates/includes/header.php";

$total_items = $conn->query("SELECT COUNT(*) FROM inventory")->fetchColumn();
$total_stock = $conn->query("SELECT SUM(quantity) FROM inventory")->fetchColumn();

$total_requests = $conn->query("SELECT COUNT(*) FROM requests")->fetchColumn();
$approved = $conn->query("SELECT COUNT(*) FROM requests WHERE status='APPROVED'")->fetchColumn();
$rejected = $conn->query("SELECT COUNT(*) FROM requests WHERE status='REJECTED'")->fetchColumn();
$pending = $conn->query("SELECT COUNT(*) FROM requests WHERE status='PENDING'")->fetchColumn();
?>

<h2>Reports</h2>

<p>Total Items: <?= $total_items ?></p>
<p>Total Stock: <?= $total_stock ?></p>

<hr>

<p>Total Requests: <?= $total_requests ?></p>
<p>Approved: <?= $approved ?></p>
<p>Rejected: <?= $rejected ?></p>
<p>Pending: <?= $pending ?></p>

<br>
<a href="<?= url('dashboard') ?>" class="btn btn-secondary">Back</a>

<?php require_once ROOT_PATH . "templates/includes/footer.php"; ?>

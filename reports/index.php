<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}

require_once __DIR__ . "/../includes/header.php";

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
<a href="/film_studio/index.php" class="btn btn-secondary">Back</a>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>

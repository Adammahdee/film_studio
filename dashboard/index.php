<?php 
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/header.php";
?>

<h2>Dashboard</h2>

<p>Role: <?= htmlspecialchars($_SESSION['role'] ?? '') ?></p>

<?php if ($_SESSION['role'] == 'ADMIN' || $_SESSION['role'] == 'MANAGER'): ?>
    <a href="/film_studio/inventory/index.php">Manage Inventory</a><br><br>
    <a href="/film_studio/requests/approve.php">Approve Requests</a><br><br>
<?php endif; ?>

<?php if ($_SESSION['role'] == 'STAFF'): ?>
    <a href="/film_studio/requests/create.php">Request Item</a><br><br>
<?php endif; ?>

<a href="/film_studio/auth/logout.php">Logout</a>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>

<?php 
require_once "auth_check.php";
?>

<h2>Dashboard</h2>

<p>Role: <?php echo $_SESSION['role']; ?></p>

<?php if ($_SESSION['role'] == 'ADMIN' || $_SESSION['role'] == 'MANAGER'): ?>
    <a href="inventory.php">Manage Inventory</a><br><br>
    <a href="approve.php">Approve Requests</a><br><br>
<?php endif; ?>

<?php if ($_SESSION['role'] == 'STAFF'): ?>
    <a href="request.php">Request Item</a><br><br>
<?php endif; ?>

<a href="logout.php">Logout</a>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Ensure auth check is run if not already included by the parent page
require_once __DIR__ . "/auth_check.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Film Studio System</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="/film_studio/index.php">Film Studio</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarText">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarText">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="/film_studio/index.php">Dashboard</a></li>
        <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'ADMIN' || $_SESSION['role'] === 'MANAGER')): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="inventoryDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Inventory
          </a>
          <ul class="dropdown-menu" aria-labelledby="inventoryDropdown">
            <li><a class="dropdown-item" href="/film_studio/inventory/index.php">View Inventory</a></li>
            <li><a class="dropdown-item" href="/film_studio/inventory/add_item.php">Add Item</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="suppliersDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Suppliers
          </a>
          <ul class="dropdown-menu" aria-labelledby="suppliersDropdown">
            <li><a class="dropdown-item" href="/film_studio/suppliers/index.php">Manage Suppliers</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="procurementDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Procurement
          </a>
          <ul class="dropdown-menu" aria-labelledby="procurementDropdown">
            <li><a class="dropdown-item" href="/film_studio/purchase_orders/index.php">Purchase Orders</a></li>
            <li><a class="dropdown-item" href="/film_studio/purchase_orders/create.php">Create Purchase Order</a></li>
            <li><a class="dropdown-item" href="/film_studio/requests/approve.php">Approve Requests</a></li>
            <li><a class="dropdown-item" href="/film_studio/requests/index.php">View All Requests</a></li>
          </ul>
        </li>
        <?php endif; ?>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'STAFF'): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="requestsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Requests
          </a>
          <ul class="dropdown-menu" aria-labelledby="requestsDropdown">
            <li><a class="dropdown-item" href="/film_studio/requests/create.php">Create Request</a></li>
            <li><a class="dropdown-item" href="/film_studio/requests/my_requests.php">My Requests</a></li>
          </ul>
        </li>
        <?php endif; ?>
      </ul>
      <div class="d-flex text-white align-items-center gap-3">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/film_studio/profile.php" class="text-white text-decoration-none">Profile</a>
            <span class="badge bg-secondary"><?php echo htmlspecialchars($_SESSION['role'] ?? ''); ?></span>
            <a href="/film_studio/auth/logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<div class="container">

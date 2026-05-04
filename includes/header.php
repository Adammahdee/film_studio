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
    <style>
        .app-shell {
            min-height: 100vh;
        }

        .app-sidebar {
            width: 260px;
            background: #1f2937;
        }

        .app-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.78);
            border-radius: 6px;
            padding: 0.65rem 0.75rem;
        }

        .app-sidebar .nav-link:hover,
        .app-sidebar .nav-link:focus {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .app-content {
            min-width: 0;
        }

        @media (max-width: 767.98px) {
            .app-shell {
                display: block !important;
            }

            .app-sidebar {
                width: 100%;
            }
        }
    </style>
</head>
<body class="bg-light">
<div class="app-shell d-flex">
  <aside class="app-sidebar text-white p-3 p-md-4 shadow-sm">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <a class="fs-5 fw-semibold text-white text-decoration-none" href="/film_studio/index.php">Film Studio</a>
      <span class="badge bg-secondary"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></span>
    </div>

    <nav class="nav flex-column gap-1">
      <a class="nav-link" href="/film_studio/index.php">Dashboard</a>

      <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'ADMIN' || $_SESSION['role'] === 'MANAGER')): ?>
        <div class="mt-3 mb-1 small text-uppercase text-white-50">Inventory</div>
        <a class="nav-link" href="/film_studio/inventory/index.php">View Inventory</a>
        <a class="nav-link" href="/film_studio/inventory/add_item.php">Add Item</a>

        <div class="mt-3 mb-1 small text-uppercase text-white-50">Operations</div>
        <a class="nav-link" href="/film_studio/suppliers/index.php">Suppliers</a>
        <a class="nav-link" href="/film_studio/purchase_orders/index.php">Purchase Orders</a>
        <a class="nav-link" href="/film_studio/purchase_orders/create.php">Create Purchase Order</a>

        <div class="mt-3 mb-1 small text-uppercase text-white-50">Requests</div>
        <a class="nav-link" href="/film_studio/requests/approve.php">Approve Requests</a>
        <a class="nav-link" href="/film_studio/requests/index.php">View All Requests</a>
        <a class="nav-link" href="/film_studio/reports/index.php">Reports</a>
      <?php endif; ?>

      <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'STAFF'): ?>
        <div class="mt-3 mb-1 small text-uppercase text-white-50">Requests</div>
        <a class="nav-link" href="/film_studio/requests/create.php">Create Request</a>
        <a class="nav-link" href="/film_studio/requests/my_requests.php">My Requests</a>
      <?php endif; ?>
    </nav>

    <div class="border-top border-secondary mt-4 pt-3">
      <a href="/film_studio/profile.php" class="nav-link px-0 text-white-50">Profile</a>
      <a href="/film_studio/auth/logout.php" class="btn btn-outline-light btn-sm w-100 mt-2">Logout</a>
    </div>
  </aside>

  <main class="app-content flex-grow-1 p-3 p-md-4">
    <div class="container-fluid">

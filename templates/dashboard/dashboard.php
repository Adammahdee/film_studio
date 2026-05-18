<?php
use App\Core\ErrorHandler;

// Ensure the user identity context exists before rendering the panel canvas
$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? 'STAFF';
$sessionSig = substr(session_id(), 0, 8) . '...';

// Safely pull counts from your global database dependency helper arrays for metrics
// Note: If these aren't pre-calculated in your router, they will fall back to your default placeholders cleanly.
$totalItems = $dashboardMetrics['total_items'] ?? 11;
$pendingOrders = $dashboardMetrics['pending_orders'] ?? 0;
$lowStockAlerts = $dashboardMetrics['low_stock'] ?? 3;
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
    body { background-color: #f8f9fa !important; color: #212529; font-family: system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif; }
    .transition-hover { transition: all 0.2s ease-in-out; }
    .transition-hover:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.08)!important; }
    .text-mono { font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    .card { border-radius: 0.5rem !important; }
</style>

<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-dark text-white border-0 shadow-sm overflow-hidden position-relative">
            <div class="card-body p-4 p-md-5 position-relative z-1">
                <span class="badge bg-primary px-3 py-2 mb-2 text-uppercase tracking-wider fw-semibold">Studio Command Console</span>
                <h1 class="display-5 fw-bold mb-1">Welcome Back, <?= htmlspecialchars($username) ?>.</h1>
                <p class="lead text-white-50 mb-0">Production facilities, procurement requests, and asset configurations are operating within normal parameters.</p>
            </div>
            <div class="position-absolute end-0 bottom-0 opacity-10 p-5 display-1 icon-background"><i class="bi bi-film"></i></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm bg-white">
            <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0 bg-primary bg-opacity-10 text-primary rounded-3 p-3 me-3">
                    <i class="bi bi-box-seam fs-2 lh-1"></i>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted text-uppercase mb-1 small fw-semibold">Inventory Items</h6>
                    <h2 class="card-title mb-0 fw-bold text-dark"><?= (int)$totalItems ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm bg-white">
            <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0 <?= $pendingOrders > 0 ? 'bg-warning text-warning' : 'bg-light text-secondary' ?> bg-opacity-10 rounded-3 p-3 me-3">
                    <i class="bi bi-receipt fs-2 lh-1"></i>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted text-uppercase mb-1 small fw-semibold">Pending Orders</h6>
                    <h2 class="card-title mb-0 fw-bold <?= $pendingOrders > 0 ? 'text-warning' : 'text-dark' ?>"><?= (int)$pendingOrders ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm bg-white">
            <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0 <?= $lowStockAlerts > 0 ? 'bg-danger text-danger animate-pulse' : 'bg-success text-success' ?> bg-opacity-10 rounded-3 p-3 me-3">
                    <i class="bi bi-exclamation-triangle fs-2 lh-1"></i>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted text-uppercase mb-1 small fw-semibold">Low Stock Alerts</h6>
                    <h2 class="card-title mb-0 fw-bold <?= $lowStockAlerts > 0 ? 'text-danger' : 'text-success' ?>"><?= (int)$lowStockAlerts ?></h2>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-1">
                <h5 class="card-title fw-bold mb-0 text-secondary"><i class="bi bi-grid-1x2-fill me-2 text-primary"></i>Quick Navigation Modules</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 h-100 transition-hover">
                            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-camera-video me-2 text-primary"></i>Gear & Assets</h6>
                            <p class="text-muted small mb-3">Add new equipment profiles, edit resource parameters, or deplete item stocks.</p>
                            <div class="d-flex gap-2">
                                <a href="<?= url('inventory') ?>" class="btn btn-sm btn-primary">Manage Gear</a>
                                <a href="<?= url('inventory', 'add') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 h-100 transition-hover">
                            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-card-checklist me-2 text-success"></i>Fulfillment Channels</h6>
                            <p class="text-muted small mb-3">Authorise internal distribution requests or audit ongoing procurement actions.</p>
                            <div class="d-flex gap-2">
                                <a href="<?= url('requests') ?>" class="btn btn-sm btn-success">View Requests</a>
                                <a href="<?= url('reports') ?>" class="btn btn-sm btn-outline-success">Analytics</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="p-3 border rounded-3 bg-light d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="bg-secondary bg-opacity-10 text-secondary p-2 rounded-3 me-3">
                                    <i class="bi bi-building fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Supplier Directory</h6>
                                    <p class="text-muted small mb-0">Manage global sourcing channels and production vendors.</p>
                                </div>
                            </div>
                            <a href="<?= url('suppliers') ?>" class="btn btn-sm btn-outline-dark px-3">Enter Registry</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-1">
                <h5 class="card-title fw-bold mb-0 text-secondary"><i class="bi bi-shield-lock-fill me-2 text-dark"></i>Session Token Metrics</h5>
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div class="bg-light p-3 rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                        <span class="text-muted small">Signature Index</span>
                        <span class="text-mono small fw-bold text-dark"><?= htmlspecialchars($sessionSig) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Clearance Rank</span>
                        <span class="badge bg-danger text-uppercase fw-semibold px-2.5 py-1"><?= htmlspecialchars($role) ?></span>
                    </div>
                </div>
                
                <div class="d-grid">
                    <a href="<?= url('auth', 'logout') ?>" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2 py-2 fw-semibold">
                        <i class="bi bi-box-arrow-left"></i> Sign Out Safely
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
require_once __DIR__ . "/includes/auth_check.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/header.php";

// Fetch user role to ensure only admins have access
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user['role'] !== 'ADMIN') {
    echo '<div class="alert alert-danger">Access Denied. Admins only.</div>';
    require_once __DIR__ . "/includes/footer.php";
    exit();
}

$msg = "";

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $conn->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, trim($value)]);
        }
        $msg = '<div class="alert alert-success">Settings updated successfully.</div>';
    } catch (PDOException $e) {
        $msg = '<div class="alert alert-danger">Error updating settings.</div>';
    }
}

// Fetch Current Settings
$settings = [];
$stmt = $conn->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="card-title mb-0">System Settings</h5>
            </div>
            <div class="card-body">
                <?= $msg ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Site Name</label>
                        <input type="text" name="settings[site_name]" class="form-control" 
                               value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admin Contact Email</label>
                        <input type="email" name="settings[contact_email]" class="form-control" 
                               value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Studio Logo URL</label>
                        <input type="url" name="settings[studio_logo_url]" class="form-control" 
                               value="<?= htmlspecialchars($settings['studio_logo_url'] ?? '') ?>" placeholder="https://example.com/logo.png">
                        <div class="form-text">Provide a link to your studio's logo image.</div>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input type="hidden" name="settings[maintenance_mode]" value="0">
                        <input class="form-check-input" type="checkbox" name="settings[maintenance_mode]" id="maintenanceMode" value="1" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="maintenanceMode">
                            Enable Maintenance Mode
                        </label>
                        <div class="form-text">If enabled, non-admin users will be redirected to a maintenance page.</div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <a href="/index.php?page=dashboard" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Configuration</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-4 shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="card-title mb-0">Database Management</h5>
            </div>
            <div class="card-body">
                <p>Manage your system data by performing backups or restoring from a previous state.</p>
                <a href="/index.php?page=backup" class="btn btn-outline-primary">Go to Backup/Restore</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
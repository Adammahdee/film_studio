<?php
use App\Core\Csrf;
use App\Auth\Permissions;
use App\Core\ErrorHandler;
use App\Services\AuditLogger;
use App\Core\DatabaseTransaction;
require_once ROOT_PATH . "src/Auth/auth_check.php";
require_once ROOT_PATH . "templates/includes/header.php";

// Fetch user role to ensure only admins have access
$user_id = $_SESSION['user_id'];
// Use Permissions helper for access control
if (!Permissions::hasPermission($_SESSION['role'] ?? '', 'system_settings')) {
    // For now, just die. Later, use ErrorHandler::render403()
    die('<div class="alert alert-danger">Access Denied. You do not have permission to view system settings.</div>');
    // require_once ROOT_PATH . "templates/includes/footer.php"; // This line was problematic with die()
    exit();
}

$msg = "";

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // This will be replaced by Permissions::hasPermission later
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        $msg = '<div class="alert alert-danger">Invalid CSRF token. Please try again.</div>';
        error_log("CSRF attack detected on settings form from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
    } else {
        try {
            DatabaseTransaction::begin();
            $oldSettings = [];
            foreach ($_POST['settings'] as $key => $value) {
                // Fetch old value for logging
                $oldStmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                $oldStmt->execute([$key]);
                $oldSettings[$key] = $oldStmt->fetchColumn();
                $stmt = $conn->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute([$key, trim($value)]);
            }
            AuditLogger::log('UPDATE', 'System Settings', null, $oldSettings, $_POST['settings']);
            DatabaseTransaction::commit();
            $msg = '<div class="alert alert-success">Settings updated successfully.</div>';
        } catch (PDOException $e) {
            DatabaseTransaction::rollback();
            $msg = '<div class="alert alert-danger">Error updating settings: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
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
                    <input type="hidden" name="csrf_token" value="<?= Csrf::generateToken() ?>">
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
                        <a href="<?= url('dashboard') ?>" class="btn btn-secondary">Cancel</a>
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
                <a href="<?= url('backup') ?>" class="btn btn-outline-primary">Go to Backup/Restore</a>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . "templates/includes/footer.php"; ?>
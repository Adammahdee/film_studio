<?php
use App\Core\Csrf;
use App\Auth\Permissions;
use App\Core\ErrorHandler;
use App\Core\AuditLogger;
use App\Core\DatabaseTransaction;

// Fetch user identity metrics to run access permission verification
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id || !Permissions::hasPermission($_SESSION['role'] ?? '', 'system_settings')) {
    // Upgraded from hardcoded die() to your professional centralized framework component
    ErrorHandler::render403();
    exit();
}

$msg = "";

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        $msg = '<div class="alert alert-danger">Invalid CSRF token. Please try again.</div>';
        error_log("CSRF attack detected on settings form from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
    } else {
        try {
            DatabaseTransaction::begin();
            $oldSettings = [];
            
            if (isset($_POST['settings']) && is_array($_POST['settings'])) {
                foreach ($_POST['settings'] as $key => $value) {
                    // Swapped $conn for global $pdo reference
                    $oldStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                    $oldStmt->execute([$key]);
                    $oldSettings[$key] = $oldStmt->fetchColumn();
                    
                    $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
                    $stmt->execute([$key, trim($value)]);
                }
                
                AuditLogger::log('UPDATE', 'System Settings', null, $oldSettings, $_POST['settings']);
                DatabaseTransaction::commit();
                $msg = '<div class="alert alert-success">Settings updated successfully.</div>';
            }
        } catch (PDOException $e) {
            DatabaseTransaction::rollback();
            $msg = '<div class="alert alert-danger">Error updating settings: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Fetch Current Settings using $pdo
$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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

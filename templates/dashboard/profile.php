<?php
use App\Core\Csrf;
use App\Core\DatabaseTransaction;
use App\Services\AuditLogger;
require_once ROOT_PATH . "src/Auth/auth_check.php";
require_once ROOT_PATH . "templates/includes/header.php";

$user_id = $_SESSION['user_id'];
$msg = "";

// Fetch Current User Data (Initial fetch for form and logging state)
$stmt = $conn->prepare("SELECT full_name, role, password FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        $msg = '<div class="alert alert-danger">Invalid CSRF token. Please try again.</div>';
        error_log("CSRF attack detected on profile update form from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
    } else {
        if (empty($full_name)) {
            $msg = '<div class="alert alert-danger">Full Name is required.</div>'; // This could use a validator
        } else {
            try {
                $oldUserData = ['full_name' => $user['full_name'], 'password_set' => !empty($user['password'])]; // Capture old state
                DatabaseTransaction::begin();
                if (!empty($password)) {
                    // Update name and password
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET full_name = ?, password = ? WHERE user_id = ?");
                    $stmt->execute([$full_name, $hash, $user_id]);
                } else {
                    // Update name only
                    $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
                    $stmt->execute([$full_name, $user_id]);
                }
                $newUserData = ['full_name' => $full_name, 'password_set' => !empty($password)];
                AuditLogger::log('UPDATE', 'Profile', $user_id, $oldUserData, $newUserData);
                DatabaseTransaction::commit();
                $msg = '<div class="alert alert-success">Profile updated successfully.</div>';
            } catch (PDOException $e) {
                DatabaseTransaction::rollback();
                $msg = '<div class="alert alert-danger">Error updating profile: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">My Profile</h5>
            </div>
            <div class="card-body">
                <?= $msg ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Csrf::generateToken() ?>">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['role']) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control" placeholder="New Password">
                    </div>
                    <button type="submit" class="btn btn-success w-100">Update Profile</button>
                </form>
            </div>
        </div>
        <div class="mt-3 text-center">
            <a href="<?= url('dashboard') ?>" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . "templates/includes/footer.php"; ?>

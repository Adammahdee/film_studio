<?php

use App\Core\Csrf;
use App\Core\DatabaseTransaction;
use App\Core\AuditLogger;

// Session check
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: " . url('auth'));
    exit();
}

$msg = "";

// Load user data (safe preload layer)
try {
    $stmt = $pdo->prepare("
        SELECT full_name, role, password
        FROM users
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['errors'] = "User not found.";
        header("Location: " . url('dashboard'));
        exit();
    }

} catch (PDOException $e) {
    error_log("Profile load error: " . $e->getMessage());
    $_SESSION['errors'] = "System error loading profile.";
    header("Location: " . url('dashboard'));
    exit();
}

// Handle update request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        error_log("CSRF failure on profile update. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
        $msg = "Invalid request token.";
    } else {

        $full_name = trim($_POST['full_name'] ?? '');
        $password  = $_POST['password'] ?? '';

        if ($full_name === '') {
            $msg = "Full name is required.";
        } else {

            try {
                DatabaseTransaction::begin();

                $oldData = [
                    'full_name' => $user['full_name'],
                    'password_set' => !empty($user['password'])
                ];

                if ($password !== '') {

                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET full_name = ?, password = ?
                        WHERE user_id = ?
                    ");

                    $stmt->execute([$full_name, $hash, $user_id]);

                } else {

                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET full_name = ?
                        WHERE user_id = ?
                    ");

                    $stmt->execute([$full_name, $user_id]);
                }

                $newData = [
                    'full_name' => $full_name,
                    'password_set' => ($password !== '')
                ];

                AuditLogger::log(
                    'UPDATE_PROFILE',
                    'users',
                    $user_id,
                    $oldData,
                    $newData
                );

                DatabaseTransaction::commit();

                $user['full_name'] = $full_name;

                $msg = "Profile updated successfully.";

            } catch (PDOException $e) {

                DatabaseTransaction::rollback();
                error_log("Profile update failed: " . $e->getMessage());

                $msg = "System error occurred while updating profile.";
            }
        }
    }
}
?>

<!-- VIEW ONLY -->
<div class="container py-4">

    <div class="row justify-content-center">

        <div class="col-md-6">

            <div class="card shadow-sm">

                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">My Profile</h5>
                </div>

                <div class="card-body">

                    <?php if ($msg): ?>
                        <div class="alert alert-info">
                            <?= htmlspecialchars($msg) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Csrf::generateToken() ?>">

                        <div class="mb-3">
                            <label>Full Name</label>
                            <input type="text"
                                   name="full_name"
                                   class="form-control"
                                   value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label>Role</label>
                            <input type="text"
                                   class="form-control"
                                   value="<?= htmlspecialchars($user['role'] ?? '') ?>"
                                   disabled>
                        </div>

                        <div class="mb-3">
                            <label>New Password</label>
                            <input type="password"
                                   name="password"
                                   class="form-control">
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            Update Profile
                        </button>

                    </form>

                </div>

            </div>

            <div class="mt-3 text-center">
                <a href="<?= url('dashboard') ?>" class="btn btn-secondary">
                    Back
                </a>
            </div>

        </div>

    </div>

</div>

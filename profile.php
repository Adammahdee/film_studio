<?php
require_once __DIR__ . "/includes/auth_check.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/header.php";

$user_id = $_SESSION['user_id'];
$msg = "";

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];

    if (empty($full_name)) {
        $msg = '<div class="alert alert-danger">Full Name is required.</div>';
    } else {
        try {
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
            $msg = '<div class="alert alert-success">Profile updated successfully.</div>';
        } catch (PDOException $e) {
            $msg = '<div class="alert alert-danger">Error updating profile.</div>';
        }
    }
}

// Fetch Current User Data
$stmt = $conn->prepare("SELECT full_name, role FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
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
            <a href="/film_studio/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

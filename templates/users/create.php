<?php

use App\Core\Csrf;
use App\Core\ErrorHandler;
use App\Core\AuditLogger;

// Role restriction
$role = $_SESSION['role'] ?? '';
if ($role !== 'ADMIN') {
    ErrorHandler::render403();
    exit();
}

$msg = "";

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        error_log("CSRF failure on user creation. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
        $msg = "Invalid request token.";
    } else {

        $username   = trim($_POST['username'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $fullName   = trim($_POST['full_name'] ?? '');
        $roleInput  = trim($_POST['role'] ?? '');
        $password   = $_POST['password'] ?? '';

        // Validation
        if ($username === '' || $email === '' || $fullName === '' || $roleInput === '' || $password === '') {
            $msg = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = "Invalid email format.";
        } elseif (strlen($password) < 8) {
            $msg = "Password must be at least 8 characters.";
        } else {

            try {
                // Check duplicates
                $check = $pdo->prepare("
                    SELECT user_id 
                    FROM users 
                    WHERE username = ? OR email = ?
                ");
                $check->execute([$username, $email]);

                if ($check->fetch()) {
                    $msg = "Username or email already exists.";
                } else {

                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, email, full_name, role, password, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");

                    $stmt->execute([
                        $username,
                        $email,
                        $fullName,
                        $roleInput,
                        $hashedPassword
                    ]);

                    $userId = $pdo->lastInsertId();

                    AuditLogger::log(
                        'CREATE_USER',
                        'users',
                        $userId,
                        null,
                        [
                            'username' => $username,
                            'email' => $email,
                            'full_name' => $fullName,
                            'role' => $roleInput
                        ]
                    );

                    $_SESSION['success_msg'] = "User created successfully.";

                    header("Location: " . url('users'));
                    exit();
                }

            } catch (PDOException $e) {
                error_log("User creation failed: " . $e->getMessage());
                $msg = "System error occurred while creating user.";
            }
        }
    }
}
?>

<!-- VIEW ONLY -->
<div class="container py-4">

    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card shadow-sm">

                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Create User Account</h5>
                </div>

                <div class="card-body">

                    <?php if ($msg): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($msg) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Csrf::generateToken() ?>">

                        <div class="mb-3">
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Role</label>
                            <select name="role" class="form-select" required>
                                <option value="">Select role</option>
                                <option value="ADMIN">Admin</option>
                                <option value="MANAGER">Manager</option>
                                <option value="STAFF">Staff</option>
                                <option value="executive_producer">Executive Producer</option>
                                <option value="line_producer">Line Producer</option>
                                <option value="procurement_officer">Procurement Officer</option>
                                <option value="crew">Crew</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" minlength="8" required>
                            <small class="text-muted">Minimum 8 characters required</small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?= url('users') ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-dark">Create User</button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>

</div>

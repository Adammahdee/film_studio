<?php

use App\Core\Csrf;
use App\Core\AuditLogger;

require_once ROOT_PATH . 'config/db.php';

$error = null;

/**
 * LOGOUT ACTION
 * Should ideally be moved to a dedicated controller route
 */
if (isset($_GET['action']) && $_GET['action'] === 'logout') {

    if (isset($_SESSION['user_id'])) {

        $stmt = $pdo->prepare("
            UPDATE users
            SET remember_token = NULL
            WHERE user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);

        AuditLogger::log($pdo, 'LOGOUT', 'auth', $_SESSION['user_id']);
    }

    setcookie('remember_me', '', time() - 3600, "/", "", isset($_SERVER['HTTPS']), true);

    $_SESSION = [];
    session_destroy();

    header("Location: " . url('auth'));
    exit();
}

/**
 * LOGIN HANDLER
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {

        $error = "Invalid request token.";
        error_log("CSRF failure on login from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));

    } else {

        $identifier = trim($_POST['full_name'] ?? '');
        $password   = $_POST['password'] ?? '';
        $remember   = isset($_POST['remember']);

        try {

            $stmt = $pdo->prepare("
                SELECT user_id, full_name, role, password
                FROM users
                WHERE full_name = ?
                LIMIT 1
            ");

            $stmt->execute([$identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role']    = $user['role'];

                AuditLogger::log($pdo, 'LOGIN', 'auth', $user['user_id']);

                if ($remember) {

                    $token = bin2hex(random_bytes(32));
                    $tokenHash = password_hash($token, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET remember_token = ?
                        WHERE user_id = ?
                    ");

                    $stmt->execute([$tokenHash, $user['user_id']]);

                    setcookie(
                        'remember_me',
                        $user['user_id'] . ':' . $token,
                        time() + (86400 * 30),
                        "/",
                        "",
                        isset($_SERVER['HTTPS']),
                        true
                    );
                }

                header("Location: " . url('dashboard'));
                exit();

            } else {
                $error = "Invalid credentials.";
            }

        } catch (PDOException $e) {

            error_log("Login error: " . $e->getMessage());
            $error = "System error occurred.";
        }
    }
}
?>

<!-- VIEW ONLY -->
<div class="auth-container p-4 border rounded bg-white shadow-sm"
     style="max-width: 400px; margin: 100px auto;">

    <h2 class="text-center mb-4">Login</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <input type="hidden"
               name="csrf_token"
               value="<?= Csrf::generateToken() ?>">

        <div class="mb-3">
            <input name="full_name"
                   class="form-control"
                   placeholder="Full Name"
                   required>
        </div>

        <div class="mb-3">
            <input type="password"
                   name="password"
                   class="form-control"
                   placeholder="Password"
                   required>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox"
                   name="remember"
                   class="form-check-input"
                   id="rememberMe">

            <label class="form-check-label" for="rememberMe">
                Remember Me
            </label>
        </div>

        <button type="submit" class="btn btn-primary w-100">
            Login
        </button>

    </form>

</div>
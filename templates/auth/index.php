<?php
/**
 * Authentication View (Login/Logout)
 * NOTE: Auth_check.php is NOT included here to prevent recursive redirect loops.
 */
require_once ROOT_PATH . 'config/db.php';

// Handle Logout Action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Invalidate the "Remember Me" token in the database if user is logged in
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("UPDATE users SET remember_token = NULL WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
    // Clear the cookie
    setcookie('remember_me', '', time() - 3600, "/", "", isset($_SERVER['HTTPS']), true);
    $_SESSION = [];
    session_destroy();
    header("Location: " . url('auth'));
    exit();
}

$error = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE full_name = ?");
    $stmt->execute([$name]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role']    = $user['role'];

        if ($remember) {
            // Generate a random token
            $token = bin2hex(random_bytes(32));
            $tokenHash = password_hash($token, PASSWORD_DEFAULT);

            // Store the hashed token in the database
            $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE user_id = ?");
            $stmt->execute([$tokenHash, $user['user_id']]);

            // Set a secure cookie for 30 days (user_id:token)
            $cookieValue = $user['user_id'] . ':' . $token;
            setcookie('remember_me', $cookieValue, time() + (86400 * 30), "/", "", isset($_SERVER['HTTPS']), true);
        }

        header("Location: " . url('dashboard'));
        exit();
    } else {
        $error = "Invalid login credentials.";
    }
}
?>

<div class="auth-container p-4 border rounded bg-white shadow-sm" style="max-width: 400px; margin: 100px auto;">
    <h2 class="text-center mb-4">Studio Login</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form action="<?= url('auth') ?>" method="POST">
        <div class="mb-3"><input name="full_name" class="form-control" placeholder="Full Name" required autofocus></div>
        <div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="remember" class="form-check-input" id="rememberMe">
            <label class="form-check-label" for="rememberMe">Remember Me</label>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
</div>
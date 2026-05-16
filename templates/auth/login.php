<?php
require_once ROOT_PATH . 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST['full_name'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE full_name=?");
    $stmt->execute([$name]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];

        header("Location: " . url('dashboard'));
        exit();
    } else {
        echo "Invalid login<br>";
    }
}
?>

<h2>Login</h2>

<form method="POST">
    <input name="full_name" placeholder="Full Name" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
</form>

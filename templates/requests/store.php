<?php
require_once ROOT_PATH . 'src/Auth/auth_check.php';
require_once ROOT_PATH . 'config/db.php';

if ($_SESSION['role'] !== 'STAFF') {
    die("Access denied");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . url('requests', 'create'));
    exit();
}

$item_id = (int) ($_POST['item_id'] ?? 0);
$quantity = (int) ($_POST['quantity'] ?? 0);
$user_id = (int) $_SESSION['user_id'];
$error = "";

$stmt = $conn->prepare("SELECT item_id, quantity, status FROM inventory WHERE item_id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if ($quantity <= 0) {
    $error = "Quantity must be greater than zero.";
} elseif (!$item) {
    $error = "Selected inventory item was not found.";
} elseif (($item['status'] ?? '') !== 'AVAILABLE' || (int) $item['quantity'] <= 0) {
    $error = "Selected item is currently unavailable.";
} elseif ($quantity > (int) $item['quantity']) {
    $error = "Requested quantity exceeds available stock.";
}

if ($error !== "") {
    header("Location: " . url('requests', 'create', ['error' => $error]));
    exit();
}

$stmt = $conn->prepare("
    INSERT INTO requests (user_id, item_id, quantity)
    VALUES (?, ?, ?)
");
$stmt->execute([$user_id, $item_id, $quantity]);

header("Location: " . url('requests', 'create', ['success' => 'Request submitted successfully.']));
exit();

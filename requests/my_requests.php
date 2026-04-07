<?php
require_once "../config/db.php";

$inventory_id = $_POST['inventory_id'];
$quantity = $_POST['quantity'];

$stmt = $conn->prepare("
    INSERT INTO requests (inventory_id, quantity, status, created_at)
    VALUES (?, ?, 'PENDING', NOW())
");

$stmt->execute([$inventory_id, $quantity]);

header("Location: my_requests.php");
exit;
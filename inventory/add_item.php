<?php
require_once "../includes/auth_check.php";
require_once "../config/db.php";

// ONLY ADMIN OR MANAGER
if ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER') {
    die("Access denied");
}

// HANDLE FORM
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['item_name']);
    $qty = $_POST['quantity'];

    if ($name == "") {
        echo "Item name required<br>";
    } elseif ($qty < 0) {
        echo "Quantity cannot be negative<br>";
    } else {

        $status = ($qty > 0) ? 'AVAILABLE' : 'OUT_OF_STOCK';

        $stmt = $conn->prepare("INSERT INTO inventory (item_name, quantity, status)
                                VALUES (?, ?, ?)");
        $stmt->execute([$name, $qty, $status]);

        echo "Item added successfully<br>";
    }
}
?>

<h2>Add Item</h2>

<form method="POST">
    <input name="item_name" placeholder="Item Name" required>
    <input type="number" name="quantity" placeholder="Quantity" required>
    <button type="submit">Add</button>
</form>

<br>
<a href="index.php">Back to Inventory</a>
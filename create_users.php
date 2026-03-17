here is how you can create users in the database. You can run this script once to populate the users table with the specified users.









<?php
require_once "config/db.php";

$users = [
    ["admin-role", "Admin123@", "ADMIN"],
    ["manager-role", "Manager123@", "MANAGER"],
    ["staff-role", "Staff123@", "STAFF"]
];

foreach ($users as $u) {
    $hash = password_hash($u[1], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (full_name, password, role)
                            VALUES (?, ?, ?)");
    $stmt->execute([$u[0], $hash, $u[2]]);
}

echo "Users created successfully";
?>
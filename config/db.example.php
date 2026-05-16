<?php
// C:\laragon\www\film_studio\config\db.php

$host    = '127.0.0.1';
$db      = 'film_studio'; // Ensure this matches your local database name in Laragon
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, 
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log("Database Connection Failure: " . $e->getMessage());
    die("<div style='font-family:sans-serif; padding:20px; color:#cc0000;'><strong>Database Connection Error:</strong> Please check your local server configurations.</div>");
}
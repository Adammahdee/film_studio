<?php
// config/db.php

$conn = new PDO("mysql:host=localhost;dbname=film_studio", "root", "");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Alias the variable so both old and new code structures work interchangeably!
$pdo = &$conn; 
?>
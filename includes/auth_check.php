<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /film_studio/auth/login.php");
    exit();
}
?>

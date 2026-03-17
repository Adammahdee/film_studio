<?php
session_start();
session_destroy();
header("Location: /film_studio/auth/login.php");
exit();
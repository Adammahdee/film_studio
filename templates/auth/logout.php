<?php
session_destroy();
header("Location: " . url('auth'));
exit();
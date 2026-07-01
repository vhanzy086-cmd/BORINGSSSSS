<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    logAction($_SESSION['user_id'], 'logout', 'User logged out');
}

session_destroy();
header('Location: index.php');
exit();
?>
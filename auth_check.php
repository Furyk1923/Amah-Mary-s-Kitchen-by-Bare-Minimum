<?php
// auth_check.php - include this file at the top of any protected page
// Usage: require_once 'config.php'; require_once 'auth_check.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

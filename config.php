<?php
// config.php - database connection settings

// START A SESSION for use in all scripts that include this file
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'amahmarys_db');

// create connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// check connection
if ($mysqli->connect_errno) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// ---- SESSION CHECK SNIPPET ----
// Copy and paste the lines below at the top of any protected page:
//
//   require_once 'config.php';
//   require_once 'auth_check.php';
//
// This will redirect unauthenticated users to login.php
?>
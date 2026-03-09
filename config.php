<?php
// config.php - database connection settings

// START A SESSION for use in all scripts that include this file
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');

// create connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// check connection
if ($mysqli->connect_errno) {
    // handle error appropriately in production
    die("Database connection failed: " . $mysqli->connect_error);
}
?>
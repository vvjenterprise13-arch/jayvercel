<?php
// Railway MySQL environment variables automatically set થાય છે
$host = getenv('MYSQLHOST')     ?: getenv('DB_HOST') ?: 'localhost';
$port = getenv('MYSQLPORT')     ?: getenv('DB_PORT') ?: '3306';
$user = getenv('MYSQLUSER')     ?: getenv('DB_USER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: 'VMtftOTfdRbSDzULRKKVeGElXACVXxPI';
$db   = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'railway';

$conn = new mysqli($host, $user, $pass, $db, (int)$port);

if ($conn->connect_error) {
    error_log("DB Connection failed: " . $conn->connect_error);
    // Production માં error show ન કરો
    if (getenv('APP_ENV') !== 'production') {
        die("Connection failed: " . $conn->connect_error);
    }
}

$conn->set_charset("utf8mb4");
?>

<?php
session_start();
include('database/connection.php');

if (!isset($_SESSION['final_amount']) || !isset($_SESSION['address']) || !isset($_SESSION['cart'])) {
    header('Location: order-summary');
    exit();
}

// Payment page પર redirect
header('Location: payment');
exit();
?>

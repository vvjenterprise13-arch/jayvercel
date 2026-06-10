<?php
session_start();

if (isset($_GET['pid'])) {
    $product_id_to_remove = (int)$_GET['pid'];

   
    if (isset($_SESSION['cart'][$product_id_to_remove])) {
        unset($_SESSION['cart'][$product_id_to_remove]);
    }
}


header('Location: cart');
exit();
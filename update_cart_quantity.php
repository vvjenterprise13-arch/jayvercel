<?php
session_start();


if (isset($_GET['pid'])) {
    $product_id = (int)$_GET['pid'];

   
    if (isset($_SESSION['cart'][$product_id])) {
        
       
        if (isset($_GET['action'])) {
            $action = $_GET['action'];
            if ($action == 'increase') {
                $_SESSION['cart'][$product_id]++;
            } elseif ($action == 'decrease') {
                $_SESSION['cart'][$product_id]--;
      
                if ($_SESSION['cart'][$product_id] <= 0) {
                    unset($_SESSION['cart'][$product_id]);
                }
            }
        }
     
        elseif (isset($_GET['qty'])) {
            $new_quantity = (int)$_GET['qty'];
            if ($new_quantity > 0) {
                $_SESSION['cart'][$product_id] = $new_quantity;
            } else {
                
                unset($_SESSION['cart'][$product_id]);
            }
        }
    }
}


$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'cart';

header('Location: ' . $redirect_url);
exit();
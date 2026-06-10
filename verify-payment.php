<?php
session_start();
include('database/connection.php');

// Get PayU Salt from the database
$merchant_salt = '';
$creds_result = mysqli_query($conn, "SELECT payu_salt FROM credentials LIMIT 1");
if ($creds_result && $fetch_creds = mysqli_fetch_assoc($creds_result)) {
    $merchant_salt = $fetch_creds['payu_salt'] ?? '';
}

if (empty($merchant_salt)) {
    die("Configuration error. Cannot verify payment.");
}

$status = $_POST["status"] ?? '';
$txnid = $_POST["txnid"] ?? '';
$posted_hash = $_POST["hash"] ?? '';

if (empty($status) || empty($txnid) || empty($posted_hash)) {
    // Someone might have tried to access this page directly
    header("Location: index"); // Redirect to the main page
    exit;
}

// Re-generate the hash from the response and verify it
$key = $_POST['key'] ?? '';
$amount = $_POST['amount'] ?? '';
$productinfo = $_POST['productinfo'] ?? '';
$firstname = $_POST['firstname'] ?? '';
$email = $_POST['email'] ?? '';

// The reverse hash string sequence is crucial. Do not change it.
$hash_string = $merchant_salt . '|' . $status . '|||||||||||' . $email . '|' . $firstname . '|' . $productinfo . '|' . $amount . '|' . $txnid . '|' . $key;
$generated_hash = strtolower(hash('sha512', $hash_string));

// Proceed only if the hash matches and the payment was successful
if ($posted_hash === $generated_hash && $status === 'success') {
    
    $payu_payment_id = $_POST['payuMoneyId'] ?? '';
    $order_status = 'Success'; // Use 'Success' or 'Completed'
    
    // Add your database update code here.
    // Example:
    // Make sure 'order_id' is the name of the column in your 'orders' table that stores the transaction ID ($txnid).
    // $update_query = "UPDATE orders SET status = ?, payment_id = ? WHERE order_id = ?";
    // $stmt = mysqli_prepare($conn, $update_query);
    // mysqli_stmt_bind_param($stmt, "sss", $order_status, $payu_payment_id, $txnid);
    // mysqli_stmt_execute($stmt);

    // ================== MAIN CHANGE HERE ==================
    // Redirect the user to the Thank You page with Order ID and Payment ID
    header("Location: thank-you?order_id=" . urlencode($txnid) . "&payment_id=" . urlencode($payu_payment_id));
    exit; // It's important to exit after a redirect

} else {
    // If payment fails or is tampered with, send the user to the failure page
    $error_message = $_POST['error_Message'] ?? 'Transaction Failed';
    header("Location: payment-failed?reason=" . urlencode($error_message));
    exit;
}
?>
<?php
// PHP Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('database/connection.php');

// --- Input Validation: ખાતરી કરો કે URL માં order_id છે ---
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    die("Invalid access. Order ID is missing.");
}
$order_id_from_url = htmlspecialchars($_GET['order_id']);

// --- ડેટાબેઝમાંથી બધી વિગતો મેળવો ---
$app_id = '';
$secret_key = '';
$cashfree_mode = 'TEST';
$google_ads_accounts = []; // બહુવિધ Google Ads એકાઉન્ટ્સ માટે એરે
$fb_pixel_ids = []; // Facebook Pixel: બહુવિધ Pixel IDs માટે એરે

// Facebook Pixel: SQL ક્વેરીમાં meta_pixel કોલમ ઉમેરો
$query = "SELECT 
            cashfree_app_id, cashfree_secret_key, cashfree_mode,
            google_ads_id_1, google_ads_purchase_label_1,
            google_ads_id_2, google_ads_purchase_label_2,
            google_ads_id_3, google_ads_purchase_label_3,
            google_ads_id_4, google_ads_purchase_label_4,
            meta_pixel_id_1, meta_pixel_id_2, meta_pixel_id_3
          FROM credentials LIMIT 1";

$creds_result = mysqli_query($conn, $query);

if ($creds_result && $fetch_creds = mysqli_fetch_assoc($creds_result)) {
    $app_id = $fetch_creds['cashfree_app_id'] ?? '';
    $secret_key = $fetch_creds['cashfree_secret_key'] ?? '';
    $cashfree_mode = $fetch_creds['cashfree_mode'] ?? 'TEST';
    
    // 4 Google Ads એકાઉન્ટ્સ માટે ડેટા લોડ કરો
    for ($i = 1; $i <= 4; $i++) {
        $id_key = 'google_ads_id_' . $i;
        $label_key = 'google_ads_purchase_label_' . $i;

        if (isset($fetch_creds[$id_key]) && !empty($fetch_creds[$id_key])) {
            $google_ads_accounts[] = [
                'id' => $fetch_creds[$id_key],
                'label' => $fetch_creds[$label_key] ?? ''
            ];
        }
    }

    // Facebook Pixel: 3 Facebook Pixel IDs માટે ડેટા લોડ કરો
    for ($i = 1; $i <= 3; $i++) {
        $pixel_key = 'meta_pixel_id_' . $i;
        if (isset($fetch_creds[$pixel_key]) && !empty($fetch_creds[$pixel_key])) {
            $fb_pixel_ids[] = $fetch_creds[$pixel_key];
        }
    }
}

if (empty($app_id) || empty($secret_key)) {
    die("Payment gateway credentials are not configured properly.");
}

// --- Cashfree API પરથી ઓર્ડરનું સ્ટેટસ મેળવો ---
$base_url = (strtoupper($cashfree_mode) === 'PROD') 
    ? "https://api.cashfree.com/pg" 
    : "https://sandbox.cashfree.com/pg";

$CASHFREE_API_URL = $base_url . "/orders/" . $order_id_from_url;

$headers = [
    'Content-Type: application/json',
    'x-client-id: ' . $app_id,
    'x-client-secret: ' . $secret_key,
    'x-api-version: 2023-08-01'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $CASHFREE_API_URL);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// HTML માં વાપરવા માટે ડિફોલ્ટ વેલ્યુ સેટ કરો
$status_class = 'failure';
$status_title = '';
$status_message = '';
$details_html = '';

// Google Tag અને Facebook Pixel માટેના વેરિયેબલ્સ
$conversion_value = null;
$conversion_transaction_id = null;

// --- API ના જવાબને હેન્ડલ કરો ---
if ($http_status == 200) {
    $response_data = json_decode($response, true);
    
    if (isset($response_data['order_status']) && $response_data['order_status'] === 'PAID') {
        $status_class = 'success';
        $transaction_id = $response_data['cf_order_id'] ?? 'N/A';
        $payment_amount = $response_data['order_amount'] ?? '0.00';
        
        $status_title = "Payment Successful!";
        $status_message = "Your order has been placed successfully. Thank you!";
        
        $details_html = '
            <div class="details-box">
                <p><strong>Order ID:</strong> ' . htmlspecialchars($order_id_from_url) . '</p>
                <p><strong>Transaction ID:</strong> ' . htmlspecialchars($transaction_id) . '</p>
                <p><strong>Amount Paid:</strong> ₹' . htmlspecialchars($payment_amount) . '</p>
            </div>';
        
        // ટ્રેકિંગ માટે કિંમત અને ID સેટ કરો
        $conversion_value = $payment_amount;
        $conversion_transaction_id = $order_id_from_url;
        
    } else {
        $payment_status = $response_data['order_status'] ?? 'UNKNOWN';
        $status_title = "Payment Failed/Pending!";
        $status_message = "Sorry, your transaction could not be completed.";
        $details_html = '<p><strong>Status:</strong> ' . htmlspecialchars($payment_status) . '</p>';
    }

} else {
    $response_data = json_decode($response, true);
    $error_message_from_api = $response_data['message'] ?? 'An unknown error occurred during verification.';
    
    $status_title = "Verification Error!";
    $status_message = "We encountered a problem while trying to verify your transaction status.";
    $details_html = "<p><strong>Error Message:</strong> " . htmlspecialchars($error_message_from_api) . " (Status: {$http_status})</p>";
}

// સેશનમાંથી ઓર્ડર ID દૂર કરો
if (isset($_SESSION['order_id_for_verification'])) {
    unset($_SESSION['order_id_for_verification']);
}
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status</title>
    
    <?php 
    if (!empty($google_ads_accounts)): 
        $first_ads_id = $google_ads_accounts[0]['id'];
    ?>
    <!-- Google tag (gtag.js) - ડેટાબેઝમાંથી આવે છે -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($first_ads_id); ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      <?php
      foreach ($google_ads_accounts as $account) {
          echo "gtag('config', '" . htmlspecialchars($account['id'], ENT_QUOTES, 'UTF-8') . "');\n      ";
      }
      ?>
    </script>
    <?php endif; ?>

    <!-- Facebook Pixel: Base Code -->
    <?php if (!empty($fb_pixel_ids)): ?>
    <script>
      !function(f,b,e,v,n,t,s)
      {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
      n.callMethod.apply(n,arguments):n.queue.push(arguments)};
      if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
      n.queue=[];t=b.createElement(e);t.async=!0;
      t.src=v;s=b.getElementsByTagName(e)[0];
      s.parentNode.insertBefore(t,s)}(window, document,'script',
      'https://connect.facebook.net/en_US/fbevents.js');
      <?php
      // દરેક Pixel ID માટે 'init' કૉલ કરો
      foreach ($fb_pixel_ids as $pixel_id) {
          echo "fbq('init', '" . htmlspecialchars($pixel_id, ENT_QUOTES, 'UTF-8') . "');\n      ";
      }
      ?>
      fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
      src="https://www.facebook.com/tr?id=<?php echo htmlspecialchars($fb_pixel_ids[0]); ?>&ev=PageView&noscript=1"
    /></noscript>
    <?php endif; ?>
    <!-- End Facebook Pixel Code -->

    <?php
    // ફક્ત ત્યારે જ Event Snippet પ્રિન્ટ કરો જો પેમેન્ટ સફળ થયું હોય
    if ($status_class === 'success' && $conversion_value !== null && !empty($google_ads_accounts)):
    ?>
    <!-- Event snippet for Google Ads Purchase - ડેટાબેઝમાંથી આવે છે -->
    <script>
      <?php
      foreach ($google_ads_accounts as $account) {
          if (!empty($account['id']) && !empty($account['label'])) {
              $send_to_string = htmlspecialchars($account['id'], ENT_QUOTES, 'UTF-8') . '/' . htmlspecialchars($account['label'], ENT_QUOTES, 'UTF-8');
      ?>
      gtag('event', 'conversion', {
          'send_to': '<?php echo $send_to_string; ?>',
          'value': <?php echo json_encode(floatval($conversion_value)); ?>,
          'currency': 'INR',
          'transaction_id': <?php echo json_encode($conversion_transaction_id); ?>
      });
      <?php
          }
      }
      ?>
    </script>
    <?php endif; ?>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Your CSS code remains the same */
        :root {
            --success-color: #28a745;
            --failure-color: #dc3545;
            --primary-font: 'Poppins', sans-serif;
            --text-color: #333;
            --subtle-text-color: #666;
            --bg-color: #f4f7f6;
            --card-bg-color: #ffffff;
            --button-bg: #2874f0;
            --button-hover-bg: #1e63d0;
        }
        body {
            font-family: var(--primary-font);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--bg-color);
            margin: 0;
            color: var(--text-color);
        }
        .container {
            background-color: var(--card-bg-color);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            width: 90%;
            max-width: 480px;
            text-align: center;
            border-top: 5px solid;
            transition: border-color 0.3s;
        }
        .container.success { border-color: var(--success-color); }
        .container.failure { border-color: var(--failure-color); }
        .icon-wrapper { margin: 0 auto 20px; width: 80px; height: 80px; border-radius: 50%; display: flex; justify-content: center; align-items: center; }
        .success .icon-wrapper { background-color: rgba(40, 167, 69, 0.1); }
        .failure .icon-wrapper { background-color: rgba(220, 53, 69, 0.1); }
        .icon-wrapper svg { width: 40px; height: 40px; }
        .success .icon-wrapper svg { fill: var(--success-color); }
        .failure .icon-wrapper svg { fill: var(--failure-color); }
        .status-title { font-size: 26px; font-weight: 600; margin-bottom: 10px; }
        .success .status-title { color: var(--success-color); }
        .failure .status-title { color: var(--failure-color); }
        .status-message { font-size: 16px; color: var(--subtle-text-color); margin-bottom: 30px; }
        .details-box { background-color: #f9f9f9; border: 1px solid #eee; border-radius: 6px; padding: 20px; text-align: left; margin-bottom: 30px; }
        .details-box p { margin: 0 0 12px; color: var(--subtle-text-color); font-size: 14px; }
        .details-box p:last-child { margin-bottom: 0; }
        .details-box strong { color: var(--text-color); min-width: 120px; display: inline-block; }
        .home-button { display: inline-block; padding: 12px 30px; background-color: var(--button-bg); color: #fff; text-decoration: none; border-radius: 5px; font-weight: 500; font-size: 16px; transition: background-color 0.3s, box-shadow 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .home-button:hover { background-color: var(--button-hover-bg); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
    </style>
</head>
<body>
    <div class="container <?php echo $status_class; ?>">
        <div class="icon-wrapper">
            <?php if ($status_class === 'success'): ?>
                <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"></path></svg>
            <?php else: ?>
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"></path></svg>
            <?php endif; ?>
        </div>
        <h2 class="status-title"><?php echo $status_title; ?></h2>
        <p class="status-message"><?php echo $status_message; ?></p>
        
        <?php echo $details_html; ?>

        <a href="/" class="home-button">Go Back to Homepage</a>
    </div>

    <!-- Facebook Pixel: Purchase Event -->
    <?php
    // ફક્ત ત્યારે જ 'Purchase' ઇવેન્ટ ફાયર કરો જો પેમેન્ટ સફળ થયું હોય અને Pixel ID હાજર હોય
    if ($status_class === 'success' && $conversion_value !== null && !empty($fb_pixel_ids)):
    ?>
    <script>
      fbq('track', 'Purchase', {
        value: <?php echo json_encode(floatval($conversion_value)); ?>,
        currency: 'INR',
        content_ids: [<?php echo json_encode($conversion_transaction_id); ?>],
        content_type: 'product'
      });
    </script>
    <?php endif; ?>
    <!-- End Facebook Pixel Purchase Event -->

</body>
</html>
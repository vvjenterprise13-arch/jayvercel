<?php
session_start();
include('database/connection.php');

// Initialize an array to hold multiple Google Ads accounts
$google_ads_accounts = [];

// Fetch Google Ads tags from the database
if ($conn) {
    $sql = "SELECT
                google_ads_id_1, google_ads_purchase_label_1,
                google_ads_id_2, google_ads_purchase_label_2,
                google_ads_id_3, google_ads_purchase_label_3,
                google_ads_id_4, google_ads_purchase_label_4
            FROM credentials LIMIT 1";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        for ($i = 1; $i <= 4; $i++) {
            $id_key = 'google_ads_id_' . $i;
            $label_key = 'google_ads_purchase_label_' . $i;
            if (!empty($row[$id_key])) {
                $google_ads_accounts[] = [
                    'id' => htmlspecialchars($row[$id_key]),
                    'label' => htmlspecialchars($row[$label_key] ?? '')
                ];
            }
        }
    }
    $conn->close();
}

$order_id = $_GET['oid'] ?? 'N/A';
$payment_id = $_GET['pid'] ?? 'N/A';
$status = $_GET['status'] ?? 'N/A';
$amount = $_GET['amount'] ?? 'N/A';
$token = $_GET['token'] ?? 'N/A';

if (is_numeric($amount)) {
    $formatted_amount = '₹' . number_format($amount, 2);
    $raw_amount = floatval($amount); // Keep raw amount for Google Ads
} else {
    $formatted_amount = 'N/A';
    $raw_amount = 0.0; // Default to 0 for Google Ads if not numeric
}

$status_text = ($status === 'success') ? 'Confirmed' : 'Pending/Failed';
$status_class = ($status === 'success') ? 'text-success' : 'text-warning';
$icon_class = ($status === 'success') ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
$icon_color = ($status === 'success') ? '#28a745' : '#ffc107';
$bg_color_icon = ($status === 'success') ? '#e9f7eb' : '#fff3cd';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Confirmed</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Poppins', sans-serif;
      min-height: 100vh;
      margin: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 15px;
    }
    .thankyou-container {
      width: 100%;
      max-width: 600px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.1);
      text-align: center;
      padding: 20px 15px;
    }
    .success-icon {
      font-size: 4rem;
      color: <?= $icon_color ?>;
      width: 90px;
      height: 90px;
      border-radius: 50%;
      background: <?= $bg_color_icon ?>;
      display: flex;
      justify-content: center;
      align-items: center;
      margin: 0 auto 20px;
    }
    .thankyou-title {
      font-size: 1.8rem;
      font-weight: 700;
      margin-bottom: 10px;
    }
    .thankyou-subtitle {
      font-size: 0.95rem;
      color: #6c757d;
      margin-bottom: 25px;
    }
    .order-details {
      text-align: left;
      border: 1px solid #e9ecef;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 25px;
    }
    .order-details p {
      font-size: 0.9rem;
      margin-bottom: 10px;
      display: flex;
      justify-content: space-between;
      border-bottom: 1px dashed #eee;
      padding-bottom: 6px;
    }
    .order-details p:last-child {
      border-bottom: none;
      margin-bottom: 0;
    }
    .btn-continue {
      background: #fb641b;
      color: #fff;
      font-size: 1rem;
      padding: 12px 25px;
      border-radius: 6px;
      text-decoration: none;
      display: inline-block;
    }
    .btn-continue:hover {
      background: #e0540c;
      color: #fff;
    }

    /* Mobile adjustments */
    @media(max-width: 480px) {
      .thankyou-title { font-size: 1.5rem; }
      .thankyou-subtitle { font-size: 0.85rem; }
      .order-details p { font-size: 0.8rem; }
      .btn-continue { font-size: 0.9rem; padding: 10px 20px; }
      .success-icon { font-size: 3rem; width: 70px; height: 70px; }
    }
  </style>

  <?php if ($status === 'success' && !empty($google_ads_accounts)): ?>
    <!-- Global site tag (gtag.js) - Google Ads -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= $google_ads_accounts[0]['id'] ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      <?php foreach ($google_ads_accounts as $account): ?>
        gtag('config', '<?= $account['id'] ?>');
        <?php if (!empty($account['label'])): ?>
          gtag('event', 'conversion', {
              'send_to': '<?= $account['id'] ?>/<?= $account['label'] ?>',
              'value': <?= $raw_amount ?>,
              'currency': 'INR', // Assuming Indian Rupee, adjust if needed
              'transaction_id': '<?= $order_id ?>'
          });
        <?php endif; ?>
      <?php endforeach; ?>
    </script>
  <?php endif; ?>

</head>
<body>
  <div class="thankyou-container">
    <div class="success-icon"><i class="<?= $icon_class ?>"></i></div>
    <h1 class="thankyou-title">Thank You!</h1>
    <p class="thankyou-subtitle">Your order has been placed successfully and is now confirmed.</p>
    <div class="order-details">
      <p><span>Order ID:</span><strong><?= $order_id ?></strong></p>
      <p><span>Payment ID:</span><strong><?= $payment_id ?></strong></p>
      <p><span>Status:</span><strong class="<?= $status_class ?>"><?= $status_text ?></strong></p>
      <p><span>Amount Paid:</span><strong><?= $formatted_amount ?></strong></p>
    </div>
    <p class="text-muted small">You will receive an order confirmation email shortly.</p>
    <a href="index.php" class="btn-continue">Continue Shopping</a>
  </div>
</body>
</html>
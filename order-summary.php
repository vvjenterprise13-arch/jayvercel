<?php
session_start();
ob_start(); 

include('database/connection.php');

function get_cached_query_result($conn, $sql, $types, $params, $cache_file, $cache_time_seconds) {
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time_seconds) {
        return unserialize(file_get_contents($cache_file));
    }
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    if ($types && !empty($params)) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    if (!is_dir('cache')) { mkdir('cache', 0755, true); }
    file_put_contents($cache_file, serialize($data));
    return $data;
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: index.php');
    exit();
}
if (!isset($_SESSION['address'])) {
    header('Location: address.php');
    exit();
}

$pwebsite = '';
$form_action_url = '';
$is_payment_configured = false;
$creds_result = mysqli_query($conn, "SELECT site, pgsite, gateway FROM credentials WHERE id = 1 LIMIT 1");
if ($creds_result && $fetch_creds = mysqli_fetch_assoc($creds_result)) {
    $pwebsite = isset($fetch_creds['site']) ? rtrim($fetch_creds['site'], '/') : '';
    $active_gateway = $fetch_creds['gateway'] ?? '';
    $base_pg_url = isset($fetch_creds['pgsite']) ? rtrim($fetch_creds['pgsite'], '/') : '';
    if (!empty($active_gateway) && !empty($base_pg_url)) {
        $safe_gateway_name = preg_replace("/[^a-zA-Z0-9]+/", "", $active_gateway);
        $form_action_url = $base_pg_url . '/' . $safe_gateway_name . '.php';
        $is_payment_configured = true;
    }
}

$address = $_SESSION['address'];
$cart_items = $_SESSION['cart'];
$product_ids = array_keys($cart_items);
$id_string = implode(',', array_map('intval', $product_ids));
$products_from_db = [];

if (!empty($id_string)) {
    $sql = "SELECT * FROM products WHERE id IN (" . implode(',', array_fill(0, count($product_ids), '?')) . ")";
    $types = str_repeat('i', count($product_ids));
    $cart_hash = md5($id_string);
    $cache_file_cart_products = "cache/cart_products_{$cart_hash}.cache";
    $cached_products = get_cached_query_result($conn, $sql, $types, $product_ids, $cache_file_cart_products, 300);
    foreach ($cached_products as $item) {
        $products_from_db[$item['id']] = $item;
    }
}

$active_offer = 'b2g1';
$total_quantity = array_sum($cart_items);
$total_mrp = 0;
$final_total = 0;
$free_items_count = 0;
$total_selling_price_before_offer = 0;
$expanded_cart_for_offer = [];
foreach ($cart_items as $pid => $quantity) {
    if (isset($products_from_db[$pid])) {
        for ($i = 0; $i < $quantity; $i++) { $expanded_cart_for_offer[] = $products_from_db[$pid]; }
    }
}
if ($active_offer == 'b2g1' && $total_quantity >= 3) { $free_items_count = floor($total_quantity / 3); }
if ($free_items_count > 0) {
    usort($expanded_cart_for_offer, function($a, $b) { return (float)$a['total'] <=> (float)$b['total']; });
    for ($i = 0; $i < $free_items_count; $i++) { if(isset($expanded_cart_for_offer[$i])) { $expanded_cart_for_offer[$i]['is_free'] = true; } }
}
$processed_cart = [];
foreach ($expanded_cart_for_offer as $item) {
    $pid = $item['id'];
    if (!isset($processed_cart[$pid])) { $processed_cart[$pid] = $item; $processed_cart[$pid]['quantity'] = 0; $processed_cart[$pid]['free_quantity'] = 0; }
    $processed_cart[$pid]['quantity']++;
    if (isset($item['is_free']) && $item['is_free']) { $processed_cart[$pid]['free_quantity']++; }
}
foreach ($processed_cart as $pid => $product) {
    $payable_quantity = $product['quantity'] - $product['free_quantity'];
    $total_mrp += (float)$product['price'] * $product['quantity'];
    $total_selling_price_before_offer += (float)$product['total'] * $product['quantity'];
    $final_total += (float)$product['total'] * $payable_quantity;
}
$total_item_discount = $total_mrp - $total_selling_price_before_offer;
$offer_discount = $total_selling_price_before_offer - $final_total;
$coupon_discount = 20;
$protect_fee = 20;
$final_amount = $final_total - $coupon_discount + $protect_fee;
$total_savings = $total_item_discount + $offer_discount + $coupon_discount;
$_SESSION['final_amount'] = $final_amount;

$recommendation_category = null;
$last_product_id = array_key_last($cart_items);
if (isset($products_from_db[$last_product_id])) {
    $recommendation_category = $products_from_db[$last_product_id]['category'];
}
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-67HSHXN9DV"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-67HSHXN9DV');
</script>
    <title>Order Summary</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
   <style>
    body { background-color: #f1f2f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; font-size: 13px; }
    
    .page-header { background-color: #fff; padding: 10px 16px; display: flex; align-items: center; box-shadow: 0 1px 2px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100; }
    .header-content { display: flex; align-items: center; gap: 16px; }
    .back-arrow { color: #212121; text-decoration: none; font-size: 24px; }
    .header-logo { height: 32px; width: auto; }
    .header-title { font-size: 17px; font-weight: 500; margin: 0; }
    
    .main-content { background-color: #fff; }
    .card-section { border-bottom: 8px solid #f1f2f4; padding: 12px 16px; background: #fff; }
    .progress-stepper { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; }
    .step { display: flex; flex-direction: column; align-items: center; position: relative; flex-grow: 1; text-align: center; }
    .step-circle { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 500; margin-bottom: 5px; border: 1.5px solid #dbdbdb; color: #878787; background-color: #fff; }
    .step-label { font-size: 11px; color: #878787; }
    .step.active .step-circle { color: #2874f0; border-color: #2874f0; }
    .step.active .step-label { color: #212121; font-weight: 500; }
    .step.completed .step-circle { background-color: #2874f0; color: #fff; border-color: #2874f0; font-size: 16px; line-height: 1; }
    .step.completed .step-label { color: #212121; }
    .step:not(:last-child)::after { content: ''; position: absolute; top: 11px; left: 50%; width: 100%; height: 1.5px; background-color: #dbdbdb; z-index: -1; transform: translateX(12px); }
    .address-block .change-btn { border: 1px solid #2874f0; color: #2874f0; padding: 4px 12px; border-radius: 4px; font-size: 13px; font-weight: 500; text-decoration: none; }
    .address-type-tag { background-color: #f0f2f5; color: #565656; font-size: 10px; padding: 2px 6px; border-radius: 2px; font-weight: 500; margin-left: 8px; }
    .product-card { display: flex; gap: 16px; }
    .product-name { font-size: 13px; font-weight: 500; line-height: 1.4; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; min-height: 36px; }
    .price-details-card { border: 1px solid #e0e0e0; border-radius: 8px; }
    .price-details-row { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 13px; }
    .total-amount-row { font-weight: bold; border-top: 1px dashed #e0e0e0; padding-top: 16px; margin-top: 8px; }
    .savings-banner { background-color: #e4f8e8; color: #388e3c; font-weight: 500; padding: 12px; border-radius: 8px; }
    .page-footer { background: #fff !important; border-top: 1px solid #e0e0e0 !important; position: fixed; bottom: 0; left: 0; right: 0; padding: 10px 16px; box-shadow: 0 -2px 5px rgba(0,0,0,0.1); z-index: 101; }
    .footer-price { font-size: 17px; font-weight: bold; }
    .continue-btn { background-color: #ffc107; color: #000; border: none; padding: 12px; font-size: 15px; font-weight: 500; border-radius: 4px; }
    .continue-btn:disabled { background-color: #e0e0e0; color: #878787; cursor: not-allowed; }
    .actions-container { display: flex; align-items: center; gap: 16px; }
    .remove-link { font-weight: 500; text-transform: uppercase; color: #dc3545; text-decoration: none; font-size: 12px; }
    .address-block p { word-wrap: break-word; overflow-wrap: break-word; white-space: normal; }
    
    .suggestions-section { padding: 16px; background-color: #fff; }
    .suggestions-title { font-size: 16px; font-weight: 500; color: #212121; }
    .suggestions-subtitle { font-size: 13px; color: #878787; margin-bottom: 16px; }
    .suggestions-scroll { display: flex; overflow-x: auto; gap: 12px; padding-bottom: 10px; }
    .suggestions-scroll::-webkit-scrollbar { display: none; }
    .suggestions-scroll { -ms-overflow-style: none; scrollbar-width: none; }
    .suggested-product-card { min-width: 150px; width: 150px; border: 1px solid #e0e0e0; border-radius: 8px; padding: 10px; background-color: #fff; }
    .suggested-product-card img { width: 100%; height: 120px; object-fit: contain; margin-bottom: 8px; }
    .suggested-product-card .product-name { font-size: 13px; height: 36px; overflow: hidden; line-height: 1.3; color: #212121; }
    .suggested-product-card .price-line { display: flex; align-items: center; gap: 6px; font-size: 13px; margin-top: 6px; }
    .suggested-product-card .add-to-cart-btn { width: 100%; border: 1px solid #e0e0e0; background-color: #fff; color: #2874f0; font-weight: 500; padding: 8px; border-radius: 4px; margin-top: 10px; font-size: 14px; text-decoration: none; display: block; text-align: center; }
</style>
</head>
<body style="padding-bottom: 90px;">

    <header class="page-header">
        <div class="header-content">
            <a href="address.php" class="back-arrow"><i class="bi bi-arrow-left"></i></a>
            <img src="<?php echo htmlspecialchars($pwebsite); ?>/assets/catogary/logo.png" alt="Logo" class="header-logo">
            <h4 class="header-title">Order Summary</h4>
        </div>
    </header>

    <main class="main-content">
        <div class="progress-stepper">
            <div class="step completed"><div class="step-circle">✓</div><div class="step-label">Address</div></div>
            <div class="step active"><div class="step-circle">2</div><div class="step-label">Order Summary</div></div>
            <div class="step"><div class="step-circle">3</div><div class="step-label">Payment</div></div>
        </div>
        <div class="card-section address-block">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0 fw-bold">Deliver to:</h6><a href="address.php" class="change-btn">Change</a>
            </div>
            <p class="mb-1 fw-bold"><?php echo htmlspecialchars($address['name'] ?? ''); ?> <span class="address-type-tag"><?php echo strtoupper(htmlspecialchars($address['address_type'] ?? '')); ?></span></p>
            <p class="text-muted small mb-1"><?php echo htmlspecialchars($address['flat'] ?? '') . ', ' . htmlspecialchars($address['area'] ?? '') . ', ' . htmlspecialchars($address['city'] ?? ''); ?></p>
            <p class="text-muted small mb-0"><?php echo htmlspecialchars($address['number'] ?? ''); ?></p>
        </div>
        <div class="card-section">
        <?php foreach ($processed_cart as $pid => $product): ?>
            <div class="product-card mb-4">
                <img src="<?php echo htmlspecialchars($pwebsite); ?>/assets/uploads/<?php echo htmlspecialchars($product['image']); ?>" style="width: 100px; height: 100px; object-fit: contain;" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <div class="product-info flex-grow-1">
                    <p class="product-name mb-2"><?php echo htmlspecialchars($product['name']); ?></p>
                    <div class="price-line mb-2">
                        <span class="fw-bold fs-6">₹<?php echo number_format((float)$product['total']); ?></span>
                        <del class="text-muted small ms-2">₹<?php echo number_format((float)$product['price']); ?></del>
                        <span class="text-success fw-bold small ms-2"><?php echo htmlspecialchars($product['discount']); ?>% off</span>
                    </div>
                    <div class="actions-container">
                        <select class="form-select form-select-sm w-auto" onchange="location = 'update_cart_quantity.php?pid=<?php echo $pid; ?>&qty=' + this.value;">
                            <?php for($i=1; $i<=10; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php if($product['quantity'] == $i) echo 'selected'; ?>>Qty: <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                        <a href="update_cart_quantity.php?pid=<?php echo $pid; ?>&qty=0" class="remove-link">Remove</a>
                    </div>
                    <?php if($product['free_quantity'] > 0): ?>
                        <div class="mt-2 small text-success fw-bold" style="font-size: 10px; background-color: #eaf5ec; padding: 8px 12px; border-radius: 5px; border-left: 5px solid #198754;">
                            <i class="bi bi-tag-fill"></i> Buy 2 Get 1 Free Applied (<?php echo $product['free_quantity']; ?> FREE)
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>

        <?php
        if ($recommendation_category) {
            $safe_category = $recommendation_category;
            $rec_sql = "SELECT * FROM products WHERE category = ? AND id NOT IN (" . implode(',', array_fill(0, count($product_ids), '?')) . ") ORDER BY RAND() LIMIT 10";
            $types = "s" . str_repeat('i', count($product_ids));
            $params = array_merge([$safe_category], $product_ids);
            $cache_file_rec = 'cache/summary_rec_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $safe_category) . '.cache';
            $recommended_array = get_cached_query_result($conn, $rec_sql, $types, $params, $cache_file_rec, 600); 

            if (!empty($recommended_array)):
        ?>
            <section class="suggestions-section card-section">
                <h5 class="suggestions-title">Recommended for You</h5>
                <p class="suggestions-subtitle">From '<?php echo htmlspecialchars($recommendation_category); ?>' category</p>
                <div class="suggestions-scroll">
                <?php foreach($recommended_array as $rec_product): ?>
                    <div class="suggested-product-card">
                        <a href="singlepageview?pid=<?php echo $rec_product['id']; ?>" class="text-decoration-none">
                            <img src="<?php echo htmlspecialchars($pwebsite) ?>/assets/uploads/<?php echo htmlspecialchars($rec_product['image']); ?>" alt="<?php echo htmlspecialchars($rec_product['name']); ?>">
                            <p class="product-name"><?php echo htmlspecialchars($rec_product['name']); ?></p>
                            <div class="price-line">
                                <span class="fw-bold text-dark">₹<?php echo number_format((float)$rec_product['total']); ?></span>
                                <del class="text-muted small">₹<?php echo number_format((float)$rec_product['price']); ?></del>
                            </div>
                        </a>
                        <a href="add_to_cart?pid=<?php echo $rec_product['id']; ?>" class="add-to-cart-btn">Add to cart</a>
                    </div>
                <?php endforeach; ?>
                </div>
            </section>
        <?php
            endif; 
        }
        ?>

        <div class="card-section">
            <div class="price-details-card p-3">
                <h6 class="fw-bold mb-3">Price Details</h6>
                <div class="price-details-row"><span>Price (<?php echo $total_quantity; ?> items)</span><span>₹<?php echo number_format($total_mrp); ?></span></div>
                <div class="price-details-row"><span>Discount</span><span class="text-success">- ₹<?php echo number_format($total_item_discount + $offer_discount); ?></span></div>
                <div class="price-details-row"><span>Coupons for you</span><span class="text-success">- ₹<?php echo number_format($coupon_discount); ?></span></div>
                <div class="price-details-row"><span>Secure Packaging Fee</span><span>₹<?php echo number_format($protect_fee); ?></span></div>
                <div class="price-details-row total-amount-row"><span>Total Amount</span><span>₹<?php echo number_format($final_amount); ?></span></div>
                <div class="savings-banner mt-2"><i class="bi bi-tag-fill"></i> You will save ₹<?php echo number_format($total_savings); ?> on this order!</div>
            </div>
        </div>
    </main>

    <footer class="page-footer">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <del class="text-muted small d-block">₹<?php echo number_format($total_mrp); ?></del>
                <span class="footer-price">₹<?php echo number_format($final_amount); ?></span>
            </div>
            
            <form action="<?php echo htmlspecialchars($form_action_url); ?>" method="POST" style="width: 50%; margin: 0;">
                <input type="hidden" name="final_amount" value="<?php echo htmlspecialchars($final_amount); ?>">
                <input type="hidden" name="mobile_number" value="<?php echo htmlspecialchars($address['number'] ?? ''); ?>">
                
                <?php if ($is_payment_configured): ?>
                    <button type="submit" class="continue-btn w-100">Continue</button>
                <?php else: ?>
                    <button type="button" class="continue-btn w-100" disabled>Payment Not Available</button>
                <?php endif; ?>
            </form>
        </div>
    </footer>

</body>
</html>
<?php
ob_end_flush();
?>

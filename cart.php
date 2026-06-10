<?php
session_start();

include('database/connection.php');

function get_cached_query_result($conn, $sql, $types, $params, $cache_file, $cache_time_seconds) {
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time_seconds) {
        return unserialize(file_get_contents($cache_file));
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];

    if ($types && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!is_dir('cache')) {
        mkdir('cache', 0755, true);
    }
    file_put_contents($cache_file, serialize($data));

    return $data;
}

$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

$pwebsite = '';
if ($conn) {
    $site_sql = "SELECT site FROM credentials LIMIT 1";
    $cache_file_site = 'cache/site_url.cache';
    $site_data = get_cached_query_result($conn, $site_sql, null, [], $cache_file_site, 86400);
    if (!empty($site_data)) {
        $pwebsite = rtrim($site_data[0]['site'], '/');
    }
}

function generate_star_rating($rating) {
    $stars_html = ''; $full_stars = floor($rating); $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
    for ($i = 0; $i < $full_stars; $i++) { $stars_html .= '<i class="bi bi-star-fill"></i>'; }
    if ($half_star) { $stars_html .= '<i class="bi bi-star-half"></i>'; }
    for ($i = 0; $i < $empty_stars; $i++) { $stars_html .= '<i class="bi bi-star"></i>'; }
    return $stars_html;
}

$active_offer = 'b2g1';
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
    <title>My Cart</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f1f2f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; font-size: 14px; }
        
        .page-header {
            background-color: #FFFFFF;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 2px 0 rgba(0,0,0,0.1);
        }
        .header-content {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .back-arrow {
            color: #212121;
            text-decoration: none;
            font-size: 24px;
        }
        .header-logo {
            height: 32px;
            width: auto;
        }
        .header-title {
            font-size: 18px;
            font-weight: 500;
            margin: 0;
            color: #212121;
        }

        .cart-tabs { display: flex; background-color: #fff; border-bottom: 1px solid #f0f0f0; position: sticky; top: 57px; z-index: 100; }
        .cart-tabs .nav-link { flex-grow: 1; text-align: center; color: #878787; font-weight: 500; padding: 12px; border: none; border-bottom: 2px solid transparent; border-radius: 0; }
        .cart-tabs .nav-link.active { color: #2874f0; border-bottom-color: #2874f0; }
        .empty-cart-container { text-align: center; padding: 60px 20px; background-color: #fff; }
        .empty-cart-container img { height: 120px; opacity: 0.6; }
        .empty-cart-container h4 { margin-top: 20px; font-size: 18px; color: #212121; }
        .shop-now-btn { background-color: #2874f0; color: white; font-weight: 500; padding: 10px 40px; border-radius: 4px; text-decoration: none; margin-top: 20px; display: inline-block; }
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
        .cart-container { background-color: #fff; margin: 8px 0; }
        .cart-item-card-inner { border-bottom: 1px solid #f0f0f0; }
        .top-discount-badge { color: #26a541; font-size: 13px; font-weight: 500; padding: 12px 16px; display: block; }
        .product-main { display: flex; gap: 12px; padding: 0 16px 16px 16px; }
        .product-image { width: 110px; height: 110px; object-fit: contain; }
        .product-details { flex-grow: 1; }
        .product-name { font-size: 14px; line-height: 1.4; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; min-height: 40px; }
        .quantity-select { border: 1px solid #ddd; border-radius: 4px; padding: 4px 8px; font-size: 14px; }
        .action-buttons { display: flex; border-top: 1px solid #f0f0f0; }
        .action-buttons .btn { flex-grow: 1; border: none; border-right: 1px solid #f0f0f0; border-radius: 0; padding: 12px; font-size: 14px; color: #565656; font-weight: 500; }
        .action-buttons .btn:last-child { border-right: none; }
        .price-details-card { padding: 16px; }
        .price-details-row { display: flex; justify-content: space-between; margin-bottom: 16px; }
        .total-amount-row { font-weight: bold; border-top: 1px solid #f0f0f0; padding-top: 16px; }
        .savings-banner { color: #388e3c; font-weight: 500; }
        .page-footer { background: #fff !important; border-top: 1px solid #e0e0e0 !important; position: fixed; bottom: 0; width: 100%; left: 0; padding: 10px 16px; box-shadow: 0 -2px 5px rgba(0,0,0,0.1); }
        .footer-price-info { font-size: 12px; color: #878787; }
        .footer-price { font-size: 18px; font-weight: bold; }
        .place-order-btn { width: 45%; background-color: #fb641b; color: white; border: none; padding: 12px; font-size: 16px; font-weight: 500; border-radius: 4px; }
    </style>
</head>
<body>

    <header class="page-header">
        <div class="header-content">
            <a href="index" class="back-arrow"><i class="bi bi-arrow-left"></i></a>
            <img src="<?php echo htmlspecialchars($pwebsite) ?>/assets/catogary/logo.png" alt="Logo" class="header-logo">
            <h4 class="header-title">My Cart</h4>
        </div>
    </header>

    <div class="cart-tabs">
        <a class="nav-link active" href="#">Cart (<?php echo array_sum($cart_items); ?>)</a>
        <a class="nav-link" href="#">Grocery</a>
    </div>

    <?php if (empty($cart_items)): 
        $suggested_sql = "SELECT * FROM products ORDER BY RAND() LIMIT 15";
        $cache_file_suggested = 'cache/empty_cart_suggestions.cache';
        $suggested_products_array = get_cached_query_result($conn, $suggested_sql, null, [], $cache_file_suggested, 900);
    ?>
        <div class="empty-cart-container">
            <img src="<?php echo htmlspecialchars($pwebsite) ?>/assets/catogary/comp.webp" alt="Empty Cart" /> 
            <h4>Your cart is empty!</h4>
            <a href="index" class="shop-now-btn">Shop now</a>
        </div>
        <?php if (!empty($suggested_products_array)): ?>
        <section class="suggestions-section">
            <h5 class="suggestions-title">Suggested for You</h5>
            <p class="suggestions-subtitle">Based on Your Activity</p>
            <div class="suggestions-scroll">
            <?php foreach($suggested_products_array as $product): ?>
                <div class="suggested-product-card">
                    <a href="singlepageview?pid=<?php echo $product['id']; ?>" class="text-decoration-none">
                        <img src="<?php echo htmlspecialchars($pwebsite) ?>/assets/uploads/<?php echo htmlspecialchars($product['image']); ?>">
                        <p class="product-name"><?php echo htmlspecialchars($product['name']); ?></p>
                        <div class="price-line"><span class="fw-bold text-dark">₹<?php echo $product['total']; ?></span><del class="text-muted">₹<?php echo $product['price']; ?></del></div>
                    </a>
                    <a href="add_to_cart?pid=<?php echo $product['id']; ?>" class="add-to-cart-btn">Add to cart</a>
                </div>
            <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

    <?php else:
        $product_ids = array_keys($cart_items);
        $id_string = implode(',', array_map('intval', $product_ids));
        $products_data = [];
        if (!empty($id_string)) {
            $sql = "SELECT * FROM products WHERE id IN ($id_string)";
            $result = mysqli_query($conn, $sql);
            while ($row = mysqli_fetch_assoc($result)) { $products_data[$row['id']] = $row; }
        }
        
        $total_mrp = 0; $total_selling_price = 0; $total_quantity = array_sum($cart_items);
        $offer_discount = 0; $free_items_count = 0;

        foreach ($cart_items as $pid => $quantity) {
            if (isset($products_data[$pid])) {
                $product = $products_data[$pid];
                $total_mrp += (float)$product['price'] * $quantity;
                $total_selling_price += (float)$product['total'] * $quantity;
            }
        }
        if ($active_offer == 'b2g1' && $total_quantity >= 3) { 
            $free_items_count = floor($total_quantity / 3); 
            $expanded_cart_for_offer = [];
            foreach ($cart_items as $pid => $quantity) {
                for ($i = 0; $i < $quantity; $i++) { $expanded_cart_for_offer[] = $products_data[$pid]; }
            }
            usort($expanded_cart_for_offer, function($a, $b) { return (float)$a['total'] <=> (float)$b['total']; });
            for ($i = 0; $i < $free_items_count; $i++) {
                if (isset($expanded_cart_for_offer[$i])) { $offer_discount += (float)$expanded_cart_for_offer[$i]['total']; }
            }
        }
        
        $total_item_discount = $total_mrp - $total_selling_price;
        $platform_fee = 0;
        $final_total = $total_selling_price - $offer_discount + $platform_fee;
        $total_savings = $total_item_discount + $offer_discount;

        $recommendation_category = null;
        $last_product_id = array_key_last($cart_items);
        if (isset($products_data[$last_product_id])) {
            $recommendation_category = $products_data[$last_product_id]['category'];
        }
    ?>
        <main style="padding-bottom: 90px;">
            <div class="cart-container">
                <?php foreach ($cart_items as $pid => $quantity):
                    if (!isset($products_data[$pid])) continue;
                    $product = $products_data[$pid];
                ?>
                <div class="cart-item-card-inner">
                    <span class="top-discount-badge">Top Discount of the Sale</span>
                    <div class="product-main">
                        <img src="<?php echo htmlspecialchars($pwebsite) ?>/assets/uploads/<?php echo htmlspecialchars($product['image']); ?>" class="product-image">
                        <div class="product-details">
                            <p class="product-name mb-2"><?php echo htmlspecialchars($product['name']); ?></p>
                            <select class="quantity-select form-select-sm w-auto" onchange="location = 'update_cart_quantity.php?pid=<?php echo $pid; ?>&qty=' + this.value;">
                                <?php for($i=1; $i<=10; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php if($quantity == $i) echo 'selected'; ?>>Qty: <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button class="btn"><i class="bi bi-bookmark"></i> Save for later</button>
                        <a href="remove_from_cart.php?pid=<?php echo $pid; ?>" class="btn text-decoration-none"><i class="bi bi-trash3"></i> Remove</a>
                        <a href="add_to_cart.php?pid=<?php echo $pid; ?>&buy_now=true" class="btn text-decoration-none"><i class="bi bi-lightning"></i> Buy this now</a>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php
                if ($recommendation_category) {
                    $safe_category = $recommendation_category;
                    $recommended_sql = "SELECT * FROM products WHERE category = ? AND id NOT IN (" . implode(',', array_fill(0, count($product_ids), '?')) . ") ORDER BY RAND() LIMIT 10";
                    $types = "s" . str_repeat('i', count($product_ids));
                    $params = array_merge([$safe_category], $product_ids);
                    $cache_file_rec = 'cache/cart_rec_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $safe_category) . '.cache';
                    $recommended_array = get_cached_query_result($conn, $recommended_sql, $types, $params, $cache_file_rec, 600);

                    if (!empty($recommended_array)):
                ?>
                    <section class="suggestions-section">
                        <h5 class="suggestions-title">More in <?php echo htmlspecialchars($recommendation_category); ?></h5>
                        <p class="suggestions-subtitle">You might also like</p>
                        <div class="suggestions-scroll">
                        <?php foreach($recommended_array as $product): ?>
                            <div class="suggested-product-card">
                                <a href="singlepageview?pid=<?php echo $product['id']; ?>" class="text-decoration-none">
                                    <img src="<?php echo htmlspecialchars($pwebsite) ?>/assets/uploads/<?php echo htmlspecialchars($product['image']); ?>">
                                    <p class="product-name"><?php echo htmlspecialchars($product['name']); ?></p>
                                    <div class="price-line"><span class="fw-bold text-dark">₹<?php echo $product['total']; ?></span><del class="text-muted">₹<?php echo $product['price']; ?></del></div>
                                </a>
                                <a href="add_to_cart.php?pid=<?php echo $product['id']; ?>" class="add-to-cart-btn">Add to cart</a>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </section>
                <?php
                    endif; 
                }
                ?>
            
                <div class="price-details-card">
                    <h6 class="fw-bold mb-3">Price Details</h6>
                    <div class="price-details-row"><span>Price (<?php echo $total_quantity; ?> items)</span><span>₹<?php echo number_format($total_mrp); ?></span></div>
                    <div class="price-details-row"><span>Discount</span><span class="text-success">- ₹<?php echo number_format($total_item_discount); ?></span></div>
                    <?php if ($offer_discount > 0): ?>
                    <div class="price-details-row">
                        <span>Offer Discount (<?php echo $free_items_count; ?> Free)</span>
                        <span class="text-success">- ₹<?php echo number_format($offer_discount); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="price-details-row"><span>Platform Fee</span><span>₹<?php echo $platform_fee; ?></span></div>
                    <hr>
                    <div class="price-details-row fw-bold fs-6 total-amount-row"><span>Total Amount</span><span>₹<?php echo number_format($final_total); ?></span></div>
                    <div class="savings-banner mt-2 text-center"><i class="bi bi-tag-fill"></i> You'll save ₹<?php echo number_format($total_savings); ?> on this order!</div>
                </div>
            </div>
        </main>
        
        <footer class="page-footer">
             <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="footer-price-info"><del>₹<?php echo number_format($total_mrp + $platform_fee); ?></del> <i class="bi bi-info-circle-fill"></i></div>
                    <div class="footer-price">₹<?php echo number_format($final_total); ?></div>
                </div>
                <a href="address" class="text-decoration-none" style="width: 50%;">
                    <button class="place-order-btn w-100">Place Order</button>
                </a>
            </div>
        </footer>
    <?php endif; ?>

</body>
</html>

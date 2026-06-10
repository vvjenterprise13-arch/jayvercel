<?php
session_start();
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

function generate_star_rating($rating) {
    $stars_html = '';
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

    for ($i = 0; $i < $full_stars; $i++) { $stars_html .= '<i class="bi bi-star-fill"></i>'; }
    if ($half_star) { $stars_html .= '<i class="bi bi-star-half"></i>'; }
    for ($i = 0; $i < $empty_stars; $i++) { $stars_html .= '<i class="bi bi-star"></i>'; }
    return $stars_html;
}

$category_name = isset($_GET['category']) ? trim($_GET['category']) : '';
if (empty($category_name)) {
    echo "<!DOCTYPE html><html><head><title>ભૂલ</title></head><body><p class='text-center p-5'>કેટેગરી પસંદ કરવામાં આવી નથી.</p></body></html>";
    exit();
}

$cart_count = (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? array_sum($_SESSION['cart']) : 0;

$pwebsite = '';
if ($conn) {
    $site_sql = "SELECT site FROM credentials LIMIT 1";
    $cache_file_site = 'cache/site_url.cache';
    $site_data = get_cached_query_result($conn, $site_sql, null, [], $cache_file_site, 86400);
    if (!empty($site_data)) {
        $pwebsite = rtrim($site_data[0]['site'], '/');
    }
}

$products_array = [];
if ($conn) {
    $safe_cache_filename_part = preg_replace('/[^a-zA-Z0-9_-]/', '', $category_name);
    $cache_file_products = "cache/category_{$safe_cache_filename_part}.cache";
    $products_sql = "SELECT * FROM products WHERE category = ?";
    $products_array = get_cached_query_result($conn, $products_sql, "s", [$category_name], $cache_file_products, 900);
}
?>
<!DOCTYPE html>
<html lang="gu-IN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category_name); ?> - પ્રોડક્ટ્સ | YourStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f1f2f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; }
        
        .page-header {
            background-color: #FFFFFF;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 1px 2px 0 rgba(0,0,0,0.1);
        }
        /* -- START: CHANGED CSS -- */
        .header-left-section {
            display: flex;
            align-items: center;
            gap: 12px; /* એરો, લોગો અને શીર્ષક વચ્ચે જગ્યા */
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
            white-space: nowrap;
        }
        /* -- END: CHANGED CSS (header-center દૂર કર્યું છે) -- */
        .header-cart a {
            color: #212121;
            text-decoration: none;
            position: relative;
            font-size: 24px;
        }
        .header-cart .badge {
            position: absolute;
            top: -8px;
            right: -10px;
            font-size: 10px;
        }

        .products-section { background-color: #f1f2f4; padding-top: 6px; }
        .mainbody { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; background-color: #e0e0e0; }
        .products { background: white; text-decoration: none; color: black; }
        .productcard { padding: 10px; display: flex; flex-direction: column; height: 100%; }
        .imagecontainer { text-align: center; }
        .productimage { width: 100%; height: 150px; object-fit: contain; }
        .product-info { padding-top: 10px; }
        .product-name { font-size: 14px; color: #212121; line-height: 1.4; height: 40px; overflow: hidden; margin-bottom: 8px; }
        .price-line { display: flex; align-items: center; flex-wrap: wrap; }
        .selling-price { font-size: 16px; font-weight: 500; color: #212121; }
        .mrp { text-decoration: line-through; color: #878787; font-size: 12px; margin: 0 8px; }
        .discount { font-size: 13px; color: #388e3c; font-weight: 500; display: flex; align-items: center;}
        .discount-arrow { margin-left: 2px; }
        .wow-offer { margin-top: 4px; display: flex; align-items: center; }
        .wow-badge { height: 18px; margin-right: 6px; }
        .wow-price { font-size: 14px; font-weight: 500; color: #212121; }
        .offer-text { font-size: 12px; color: #878787; margin-left: 6px; }
        .rating-line { display: flex; align-items: center; margin-top: 6px; }
        .rating-stars { font-size: 14px; color: #26a541; }
        .rating-stars .bi-star { color: #e0e0e0; }
        .fassured-logo-small { height: 16px; margin-left: 10px; }
        .site-footer { background-color: #172337; color: #fff; font-size: 12px; padding: 40px 15px 0; line-height: 1.5; text-align: left; }
        .footer-main-container { display: flex; flex-wrap: wrap; justify-content: space-between; max-width: 1200px; margin: 0 auto; gap: 20px; }
        .footer-column h4 { color: #878787; font-size: 12px; font-weight: 500; margin-bottom: 12px; text-transform: uppercase; }
        .footer-column ul { list-style: none; padding: 0; margin: 0; }
        .footer-column ul li { margin-bottom: 9px; }
        .footer-column ul li a { color: #fff; text-decoration: none; }
        .footer-bottom-bar { border-top: 1px solid #454d5e; padding: 25px 0; margin-top: 20px; font-size: 13px; }
        .bottom-bar-container { display: flex; justify-content: space-evenly; align-items: center; flex-wrap: wrap; gap: 15px 20px; }
        .bottom-bar-link { color: #fff; text-decoration: none; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>

    <header class="page-header">
        <!-- START: CHANGED HTML -->
        <div class="header-left-section">
            <!-- ફેરફાર 1: આ લિંક હવે JavaScript વડે પાછળ જશે -->
            <a href="#" onclick="history.back(); return false;" class="back-arrow"><i class="bi bi-arrow-left"></i></a>
            
            <!-- ફેરફાર 2: લોગો અને ટાઇટલને સેન્ટરમાંથી અહીં ખસેડવામાં આવ્યા છે -->
            <img src="<?php echo htmlspecialchars($pwebsite) ?>/assets/catogary/logo.png" alt="Logo" class="header-logo">
            <h1 class="header-title"><?php echo htmlspecialchars($category_name); ?></h1>
        </div>
        <!-- END: CHANGED HTML -->
        
        <div class="header-cart">
            <a href="cart">
                <i class="bi bi-cart3"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </header>
    
    <main class="products-section">
        <div class="mainbody">
            <?php
            if (!empty($products_array)) {
                foreach ($products_array as $fetch_product) {
                    $star_rating = isset($fetch_product['star']) ? (float)$fetch_product['star'] : 0;
                    $wow_price = round((float)$fetch_product['total'] * 0.95);
            ?>
                <a href="singlepageview?pid=<?php echo $fetch_product['id']; ?>" class="products">
                    <div class="productcard">
                        <div class="imagecontainer">
                            <img src="<?php echo htmlspecialchars($pwebsite) ?>/assets/uploads/<?php echo htmlspecialchars($fetch_product['image']); ?>" class="productimage" alt="<?php echo htmlspecialchars($fetch_product['name']); ?>" loading="lazy"/>
                        </div>
                        <div class="product-info">
                            <p class="product-name"><?php echo htmlspecialchars($fetch_product['name']); ?></p>
                            <div class="price-line">
                                <span class="selling-price">₹<?php echo number_format((float)$fetch_product['total']); ?></span>
                                <del class="mrp">₹<?php echo number_format((float)$fetch_product['price']); ?></del>
                                <span class="discount">
                                    <?php echo htmlspecialchars($fetch_product['discount']); ?>%
                                    <svg width="14" height="14" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" class="discount-arrow">
                                        <path d="M6.73461 1V8.46236L9.5535 5.63352L10.5876 6.65767L5.99384 11.2415L1.41003 6.65767L2.42424 5.63352L5.25307 8.46236V1H6.73461Z" fill="#008C00"/>
                                    </svg>
                                </span>
                            </div>
                            <div class="wow-offer">
                                <img class="wow-badge" src="<?php echo htmlspecialchars($pwebsite) ?>/assets/catogary/wow.webp" alt="WOW Offer"/> 
                                <span class="wow-price">₹<?php echo number_format($wow_price); ?></span>
                                <span class="offer-text">with 2 offers</span>
                            </div>
                            <div class="rating-line">
                                <div class="rating-stars">
                                    <?php echo generate_star_rating($star_rating); ?>
                                </div>
                                <img class="fassured-logo-small" src="<?php echo htmlspecialchars($pwebsite) ?>/assets/catogary/fa_62673a.png" alt="F-Assured"/>
                            </div>
                        </div>
                    </div>
                </a>
            <?php
                }
            } else {
                echo "<div class='w-100 p-5 bg-white text-center' style='grid-column: 1 / -1;'>આ કેટેગરીમાં કોઈ પ્રોડક્ટ મળી નથી.</div>";
            }
            ?>
        </div>
    </main>
    
   
    <footer class="site-footer mt-2">
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
<?php
// Vercel પર Sessions ચલાવવા માટે આ લાઈન જરૂરી છે
session_save_path('/tmp');
session_start();

include('database/connection.php');

// Vercel ફ્રેન્ડલી કેશિંગ ફંક્શન
function get_cached_query_result($conn, $sql, $types, $params, $cache_file, $cache_time_seconds) {
    // Vercel માં માત્ર /tmp ફોલ્ડરમાં જ ફાઈલ લખી શકાય છે
    $vercel_cache_dir = '/tmp/cache';
    if (!is_dir($vercel_cache_dir)) {
        mkdir($vercel_cache_dir, 0755, true);
    }
    
    $full_cache_path = $vercel_cache_dir . '/' . basename($cache_file);

    if (file_exists($full_cache_path) && (time() - filemtime($full_cache_path)) < $cache_time_seconds) {
        return unserialize(file_get_contents($full_cache_path));
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

    file_put_contents($full_cache_path, serialize($data));

    return $data;
}

// --- પ્રોજેક્ટ સેટિંગ્સ ---
$brandName = 'YourStore';
if ($conn) {
    $settings_data = get_cached_query_result($conn, "SELECT setting_value FROM settings WHERE setting_key = 'brand_name' LIMIT 1", null, [], 'brand_name.cache', 86400);
    if (!empty($settings_data)) {
        $brandName = htmlspecialchars($settings_data[0]['setting_value']);
    }

    $site_data = get_cached_query_result($conn, "SELECT site FROM credentials LIMIT 1", null, [], 'site_url.cache', 86400);
    $pwebsite = !empty($site_data) ? rtrim($site_data[0]['site'], '/') : '';

    $all_categories_data = get_cached_query_result($conn, "SELECT DISTINCT category FROM products WHERE category != '' ORDER BY category ASC", null, [], 'all_categories.cache', 3600);
    
    $products_array = get_cached_query_result($conn, "SELECT * FROM products LIMIT 20", null, [], 'homepage_products.cache', 600);
}

$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$canonical_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

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
?>
<!DOCTYPE html>
<html lang="gu-IN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Online | <?php echo $brandName; ?></title>
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f1f2f4; font-family: system-ui, -apple-system, sans-serif; }
        .main-container { max-width: 1248px; margin: 0 auto; background-color: #fff; }
        .page-header { background-color: #FFFFFF; padding: 8px 16px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .top-bar { display: flex; justify-content: space-between; align-items: center; }
        .logo-img { height: 38px; }
        .search-bar { display: flex; align-items: center; background-color: #f0f2f5; border-radius: 8px; padding: 8px 16px; margin-top: 10px;}
        .search-bar input { border: none; outline: none; width: 100%; background: transparent; padding-left: 10px; }
        .categories-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; padding: 15px; text-align: center; }
        .category-item img { width: 45px; height: 45px; object-fit: contain; }
        .category-label { font-size: 11px; font-weight: 500; margin-top: 5px; color: #333; }
        .mainbody { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; background-color: #e0e0e0; }
        .products { background: white; text-decoration: none; color: black; padding: 10px; }
        .productimage { width: 100%; height: 180px; object-fit: contain; }
        .product-name { font-size: 13px; height: 36px; overflow: hidden; margin: 8px 0; line-height: 1.3; }
        .selling-price { font-size: 16px; font-weight: 700; }
        .mrp { text-decoration: line-through; color: #878787; font-size: 12px; margin-left: 5px; }
        .discount { color: #388e3c; font-size: 12px; font-weight: 700; margin-left: 5px; }
        .deal-banner { background: #fff; padding: 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; }
        .sale-badge { background: #ff4081; color: white; padding: 4px 10px; border-radius: 4px; font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>

<div class="main-container">
    <header class="page-header">
        <div class="top-bar">
            <div class="d-flex align-items-center">
                <button class="btn p-0 me-2 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sideMenu"><i class="bi bi-list fs-3"></i></button>
                <img src="<?php echo $pwebsite ?>/assets/catogary/svg-image-1.svg" alt="Logo" class="logo-img">
            </div>
            <div class="cart-link">
                <a href="cart" class="position-relative">
                    <i class="bi bi-cart3 fs-3 text-dark"></i>
                    <?php 
                    $cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
                    if($cart_count > 0) echo "<span class='badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill'>$cart_count</span>";
                    ?>
                </a>
            </div>
        </div>
        <div class="search-bar">
            <i class="bi bi-search text-muted"></i>
            <input type="text" placeholder="Search for Products">
        </div>
    </header>

    <main>
        <!-- Banners -->
        <div id="heroCarousel" class="carousel slide p-2" data-bs-ride="carousel">
            <div class="carousel-inner rounded-3">
                <div class="carousel-item active"><img src="<?php echo $pwebsite ?>/assets/catogary/banner1.webp" class="d-block w-100"></div>
                <div class="carousel-item"><img src="<?php echo $pwebsite ?>/assets/catogary/banner2.webp" class="d-block w-100"></div>
            </div>
        </div>

        <!-- Categories -->
        <section class="categories-container">
            <div class="categories-grid">
                <?php
                $cats = [
                    ['Mobile','mob.webp'], ['Electronics','ele.webp'], ['Appliances','kit.webp'], 
                    ['Furniture','fur.webp'], ['kurtis','kur.webp'], ['Western Wear','west.webp'],
                    ['crocs','cro.webp'], ['Shoes','shoes.webp'], ['Grocery','gro.webp'], ['dryfruit','dryfruit.webp']
                ];
                foreach($cats as $cat) {
                    echo "<div class='category-item'><a href='category_products?category={$cat[0]}'><img src='$pwebsite/assets/catogary/{$cat[1]}'><p class='category-label'>{$cat[0]}</p></a></div>";
                }
                ?>
            </div>
        </section>

        <!-- Deal Timer -->
        <div class="deal-banner">
            <div>
                <span class="fw-bold">Deals of the Day</span><br>
                <span id="timer" class="text-primary"><i class="bi bi-clock"></i> 00:00</span>
            </div>
            <div class="sale-badge">SALE IS LIVE</div>
        </div>

        <!-- Products -->
        <section class="mainbody">
            <?php
            if (!empty($products_array)) {
                foreach ($products_array as $product) {
                    $wow_price = round($product['total'] * 0.95);
            ?>
                <a href="singlepageview?pid=<?php echo $product['id']; ?>" class="products">
                    <img src="<?php echo $pwebsite ?>/assets/uploads/<?php echo $product['image']; ?>" class="productimage" loading="lazy">
                    <p class="product-name"><?php echo $product['name']; ?></p>
                    <div>
                        <span class="selling-price">₹<?php echo number_format($product['total']); ?></span>
                        <span class="mrp">₹<?php echo number_format($product['price']); ?></span>
                        <span class="discount"><?php echo $product['discount']; ?>% off</span>
                    </div>
                    <div class="mt-2">
                        <small class="text-success fw-bold">WOW Price: ₹<?php echo number_format($wow_price); ?></small>
                        <div class="text-warning small"><?php echo generate_star_rating($product['star']); ?></div>
                    </div>
                </a>
            <?php
                }
            }
            ?>
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Timer Script
    let time = 1800; 
    setInterval(() => {
        let min = Math.floor(time / 60);
        let sec = time % 60;
        document.getElementById('timer').innerHTML = `<i class="bi bi-clock"></i> ${min}:${sec < 10 ? '0'+sec : sec}`;
        time = time <= 0 ? 1800 : time - 1;
    }, 1000);
</script>
</body>
</html>
<?php include('footer.php'); ?>

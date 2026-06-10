<?php
session_save_path('/tmp');
session_start();
include('database/connection.php');

// Vercel /tmp Cache Logic
function get_cached_query_result($conn, $sql, $cache_file, $cache_time_seconds, $types = null, $params = []) {
    $vercel_cache_dir = '/tmp/cache';
    if (!is_dir($vercel_cache_dir)) { @mkdir($vercel_cache_dir, 0755, true); }
    $full_path = $vercel_cache_dir . '/' . basename($cache_file);

    if (file_exists($full_path) && (time() - filemtime($full_path)) < $cache_time_seconds) {
        return unserialize(file_get_contents($full_path));
    }

    if (!$conn) return [];
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    if ($types && !empty($params)) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!empty($data)) { @file_put_contents($full_path, serialize($data)); }
    return $data;
}

// Fetch Settings
$brandName = 'YourStore';
if ($conn) {
    $settings_data = get_cached_query_result($conn, "SELECT setting_value FROM settings WHERE setting_key = 'brand_name' LIMIT 1", 'brand_name.cache', 86400);
    if (!empty($settings_data)) { $brandName = htmlspecialchars($settings_data[0]['setting_value']); }
    
    $site_data = get_cached_query_result($conn, "SELECT site FROM credentials LIMIT 1", 'site_url.cache', 86400);
    $pwebsite = !empty($site_data) ? rtrim($site_data[0]['site'], '/') : '';

    $products_array = get_cached_query_result($conn, "SELECT * FROM products LIMIT 10", 'homepage_products.cache', 600);
}

function generate_star_rating($rating) {
    $stars_html = '';
    $full = floor((float)$rating);
    for ($i = 0; $i < $full; $i++) { $stars_html .= '<i class="bi bi-star-fill"></i>'; }
    for ($i = $full; $i < 5; $i++) { $stars_html .= '<i class="bi bi-star text-muted"></i>'; }
    return $stars_html;
}
?>
<!DOCTYPE html>
<html lang="gu-IN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $brandName; ?> - Shop Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f1f2f4; }
        .main-container { max-width: 1248px; margin: 0 auto; background-color: #fff; min-height: 100vh; }
        .page-header { background: #fff; padding: 10px; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .search-bar { background: #f0f2f5; border-radius: 8px; padding: 5px 15px; margin-top: 10px; }
        .search-bar input { border: none; background: transparent; width: 100%; outline: none; }
        .categories-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; padding: 10px; text-align: center; }
        .category-item img { width: 50px; }
        .mainbody { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; background: #e0e0e0; }
        .product-card { background: #fff; padding: 10px; text-decoration: none; color: #000; display: flex; flex-direction: column; }
        .productimage { width: 100%; height: 150px; object-fit: contain; }
        .product-name { font-size: 13px; height: 38px; overflow: hidden; margin: 5px 0; }
        .price { font-weight: bold; font-size: 16px; }
    </style>
</head>
<body>
<div class="main-container">
    <header class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <img src="<?php echo $pwebsite ?>/assets/catogary/svg-image-1.svg" height="30">
            <a href="cart" class="text-dark"><i class="bi bi-cart3 fs-4"></i></a>
        </div>
        <div class="search-bar d-flex align-items-center">
            <i class="bi bi-search me-2"></i>
            <input type="text" placeholder="Search for Products">
        </div>
    </header>

    <main>
        <div class="categories-grid">
            <div class="category-item"><a href="category_products?category=Mobile" class="text-decoration-none text-dark"><img src="<?php echo $pwebsite ?>/assets/catogary/mob.webp"><br><small>Mobiles</small></a></div>
            <div class="category-item"><a href="category_products?category=Electronics" class="text-decoration-none text-dark"><img src="<?php echo $pwebsite ?>/assets/catogary/ele.webp"><br><small>Electronics</small></a></div>
            <div class="category-item"><a href="category_products?category=Appliances" class="text-decoration-none text-dark"><img src="<?php echo $pwebsite ?>/assets/catogary/kit.webp"><br><small>Appliances</small></a></div>
            <div class="category-item"><a href="category_products?category=Furniture" class="text-decoration-none text-dark"><img src="<?php echo $pwebsite ?>/assets/catogary/fur.webp"><br><small>Furniture</small></a></div>
            <div class="category-item"><a href="category_products?category=Grocery" class="text-decoration-none text-dark"><img src="<?php echo $pwebsite ?>/assets/catogary/gro.webp"><br><small>Grocery</small></a></div>
        </div>

        <div class="mainbody" id="product-container">
            <?php foreach ($products_array as $p): ?>
                <a href="singlepageview?pid=<?php echo $p['id']; ?>" class="product-card">
                    <img src="<?php echo $pwebsite ?>/assets/uploads/<?php echo $p['image']; ?>" class="productimage">
                    <div class="product-name"><?php echo $p['name']; ?></div>
                    <div class="price">₹<?php echo number_format($p['total']); ?> <span class="text-muted small text-decoration-line-through">₹<?php echo number_format($p['price']); ?></span></div>
                    <div class="text-warning small"><?php echo generate_star_rating($p['star']); ?></div>
                    <img src="<?php echo $pwebsite ?>/assets/catogary/assured.png" height="15" class="mt-1" style="width: fit-content;">
                </a>
            <?php endforeach; ?>
        </div>
        <div id="loader" style="display:none; text-align:center; padding: 20px;"><div class="spinner-border text-primary"></div></div>
    </main>

    <?php include('footer.php'); ?>
</div>

<script>
let page = 2;
let loading = false;
window.onscroll = function() {
    if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 500) {
        if(!loading) {
            loading = true;
            document.getElementById('loader').style.display = 'block';
            fetch('/load_products?page=' + page)
                .then(res => res.text())
                .then(data => {
                    if(data.trim() !== "") {
                        document.getElementById('product-container').innerHTML += data;
                        page++;
                        loading = false;
                    }
                    document.getElementById('loader').style.display = 'none';
                });
        }
    }
};
</script>
</body>
</html>

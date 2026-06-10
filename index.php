<?php
session_save_path('/tmp');
session_start();
include('database/connection.php');

// કેશિંગ વગરનું ફંક્શન
function get_cached_query_result($conn, $sql, $types, $params, $cache_file, $cache_time_seconds) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    if ($types && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

$brandName = 'YourStore';
if ($conn) {
    $settings_sql = "SELECT setting_value FROM settings WHERE setting_key = 'brand_name' LIMIT 1";
    $settings_data = get_cached_query_result($conn, $settings_sql, null, [], '', 0);
    if (!empty($settings_data)) {
        $brandName = htmlspecialchars($settings_data[0]['setting_value']);
    }
}

$pwebsite = '';
if ($conn) {
    $site_sql = "SELECT site FROM credentials LIMIT 1";
    $site_data = get_cached_query_result($conn, $site_sql, null, [], '', 0);
    if (!empty($site_data)) {
        $pwebsite = rtrim($site_data[0]['site'], '/');
    }
}

$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$canonical_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

$products_array = [];
if ($conn) {
    $products_sql = "SELECT * FROM products LIMIT 20";
    $products_array = get_cached_query_result($conn, $products_sql, null, [], '', 0);
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
?>
<!DOCTYPE html>
<html lang="gu-IN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Online for Fashion, Electronics & More | <?php echo $brandName; ?></title>
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

   <style>
        body { background-color: #f1f2f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; }
        .main-container { max-width: 1248px; margin: 0 auto; background-color: #fff; }
        .page-header { background-color: #FFFFFF; padding: 8px 16px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.1); }
        .top-bar { display: flex; justify-content: space-between; align-items: center; }
        .logo-container .logo-img { height: 38px; vertical-align: middle; }
        .cart-link a { color: #212121; text-decoration: none; display: flex; align-items: center; position: relative; }
        .cart-icon { width: 24px; height: 24px; }
        .location-and-search { margin-top: 12px; }
        .search-bar { display: flex; align-items: center; background-color: #f0f2f5; border-radius: 8px; padding: 10px 16px; }
        .search-bar input { border: none; outline: none; width: 100%; font-size: 14px; background-color: transparent; }
        .categories-container { padding: 5px; background-color: #ffffff; }
        .categories-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; }
        .category-item a { text-decoration: none; color: #333333; display: flex; flex-direction: column; align-items: center; }
        .category-item img { width: 42px; height: 42px; margin-bottom: 6px; object-fit: contain; }
        .category-label { font-size: 12px; font-weight: 500; text-align: center; line-height: 1.2; }
        .mainbody { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; background-color: #e0e0e0; }
        .products { background: white; text-decoration: none; color: black; }
        .productcard { padding: 10px; display: flex; flex-direction: column; height: 100%; }
        .productimage { width: 100%; height: 200px; object-fit: contain; }
        .product-name { font-size: 14px; color: #212121; line-height: 1.4; height: 40px; overflow: hidden; margin-bottom: 8px; }
        .deal-banner { display: flex; justify-content: space-between; align-items: center; background: #ffffff; border-radius: 12px; padding: 20px; margin: 8px; border: 0.5px solid #e2e8f0; gap: 10px; }
        .selling-price { font-size: 16px; font-weight: 500; color: #212121; }
        .mrp { text-decoration: line-through; color: #878787; font-size: 12px; margin: 0 8px; }
        .discount { font-size: 13px; color: #388e3c; font-weight: 500; }
        .rating-stars { font-size: 14px; color: #26a541; }
        .fassured-logo-small { height: 16px; margin-left: 10px; }
    </style>
</head>
<body>

<div class="main-container">
    <header class="page-header">
        <div class="top-bar">
            <div class="d-flex align-items-center">
                <button class="btn p-0 d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sideMenu">
                    <i class="bi bi-list" style="color: #212121; font-size: 24px;"></i>
                </button>
                <div class="logo-container">
                   <img src="<?php echo $pwebsite ?>/assets/catogary/svg-image-1.svg" alt="Logo" class="logo-img">
                </div>
            </div>
            <div class="cart-link">
                <a href="cart">
                     <svg class="cart-icon" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#212121"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2zm-1.45-5c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.37-.66-.11-1.48-.87-1.48H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.24 17 6.5 17h12v-2H6.5c-.25 0-.42-.21-.38-.45l.93-1.68h7.45z"/></svg>
                </a>
            </div>
        </div>
        <div class="search-bar">
            <input type="text" placeholder="Search for Products">
        </div>
    </header>

    <main>
       <section class="categories-container">
            <div class="categories-grid">
                <div class="category-item"><a href="category_products?category=Mobile"><img src="<?php echo $pwebsite ?>/assets/catogary/mob.webp" alt="Mobiles"><p class="category-label">Mobiles</p></a></div>
                <div class="category-item"><a href="category_products?category=Electronics"><img src="<?php echo $pwebsite ?>/assets/catogary/ele.webp" alt="Electronics"><p class="category-label">Electronics</p></a></div>
                <div class="category-item"><a href="category_products?category=Appliances"><img src="<?php echo $pwebsite ?>/assets/catogary/kit.webp" alt="Appliances"><p class="category-label">Appliances</p></a></div>
                <div class="category-item"><a href="category_products?category=Furniture"><img src="<?php echo $pwebsite ?>/assets/catogary/fur.webp" alt="Furniture"><p class="category-label">Furniture</p></a></div>
                <div class="category-item"><a href="category_products?category=kurtis"><img src="<?php echo $pwebsite ?>/assets/catogary/kur.webp" alt="Sarees"><p class="category-label">Sarees</p></a></div>
                <div class="category-item"><a href="category_products?category=Western Wear"><img src="<?php echo $pwebsite ?>/assets/catogary/west.webp" alt="Western Wear"><p class="category-label">Western Wear</p></a></div>
                <div class="category-item"><a href="category_products?category=crocs"><img src="<?php echo $pwebsite ?>/assets/catogary/cro.webp" alt="Sandals"><p class="category-label">Sandals</p></a></div>
                <div class="category-item"><a href="category_products?category=Shoes"><img src="<?php echo $pwebsite ?>/assets/catogary/shoes.webp" alt="Sport Shoes"><p class="category-label">Sport Shoes</p></a></div>
                <div class="category-item"><a href="category_products?category=Grocery"><img src="<?php echo $pwebsite ?>/assets/catogary/gro.webp" alt="Grocery"><p class="category-label">Grocery</p></a></div>
                   <div class="category-item"><a href="category_products?category=dryfruit"><img src="<?php echo $pwebsite ?>/assets/catogary/dryfruit.webp" alt="Grocery"><p class="category-label">Dryfruit</p></a></div>
            </div>
        </section>

        <section class="products-section">
            <div class="mainbody">
                <?php
                if (!empty($products_array)) {
                    foreach ($products_array as $fetch_product) {
                        $star_rating = isset($fetch_product['star']) ? (float)$fetch_product['star'] : 0;
                        $wow_price = round((float)$fetch_product['total'] * 0.95);
                ?>
                    <a href="singlepageview?pid=<?php echo $fetch_product['id']; ?>" class="products">
                        <div class="productcard">
                            <div class="imagecontainer text-center">
                                <img src="<?php echo htmlspecialchars($pwebsite) ?>/assets/uploads/<?php echo htmlspecialchars($fetch_product['image']); ?>" class="productimage" loading="lazy"/>
                            </div>
                            <div class="product-info">
                                <p class="product-name"><?php echo htmlspecialchars($fetch_product['name']); ?></p>
                                <div class="price-line">
                                    <span class="selling-price">₹<?php echo number_format((float)$fetch_product['total']); ?></span>
                                    <del class="mrp">₹<?php echo number_format((float)$fetch_product['price']); ?></del>
                                    <span class="discount"><?php echo htmlspecialchars($fetch_product['discount']); ?>% off</span>
                                </div>
                                <div class="rating-line mt-2">
                                    <div class="rating-stars"><?php echo generate_star_rating($star_rating); ?></div>
                                    <img class="fassured-logo-small" src="<?php echo htmlspecialchars($pwebsite) ?>/assets/catogary/assured.png" />
                                </div>
                            </div>
                        </div>
                    </a>
                <?php
                    }
                }
                ?>
            </div>

            <!-- Loader for Infinite Scroll -->
            <div id="loader" style="display:none; text-align:center; padding: 20px;">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let page = 2;
    let isLoading = false;
    let allProductsLoaded = false;
    const loader = document.getElementById('loader');
    const mainBody = document.querySelector('.mainbody');

    function loadMoreProducts() {
        if (isLoading || allProductsLoaded) return;
        isLoading = true;
        loader.style.display = 'block';

        fetch(`load_products.php?page=${page}`)
            .then(response => response.text())
            .then(html => {
                if (html.trim() !== '') {
                    mainBody.insertAdjacentHTML('beforeend', html);
                    page++;
                } else {
                    allProductsLoaded = true;
                    loader.style.display = 'none';
                }
                isLoading = false;
            })
            .catch(error => {
                isLoading = false;
                loader.style.display = 'none';
            });
    }

    window.addEventListener('scroll', () => {
        if ((window.innerHeight + window.scrollY) >= (document.body.offsetHeight - 800)) {
            loadMoreProducts();
        }
    });
});
</script>
</body>
</html>
<?php include('footer.php'); ?>

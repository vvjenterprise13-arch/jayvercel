<?php
session_start();
include('database/connection.php');

// The get_cached_query_result function is already present in your first code, reused here.
function get_cached_query_result($conn, $sql, $types, $params, $cache_file = null, $ttl = null) {
    if (!$conn) return [];
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    if ($types && !empty($params)) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}


$pwebsite = '';
if ($conn) {
    $site_sql = "SELECT site FROM credentials LIMIT 1";
        $site_data = get_cached_query_result($conn, $site_sql, null, [],     if (!empty($site_data)) {
        $pwebsite = rtrim($site_data[0]['site'], '/');
    }
}

// Fetch all unique categories for the offcanvas menu and footer
$categories_sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
$all_categories_data = get_cached_query_result($conn, $categories_sql, null, [], $all_categories = [];
foreach ($all_categories_data as $row) {
    $all_categories[] = $row['category'];
}

// --- End of PHP for site settings and categories ---


$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$canonical_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

$products_array = [];
if ($conn) {
    $products_sql = "SELECT * FROM products LIMIT 20";
        $products_array = get_cached_query_result($conn, $products_sql, null, [], }


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
    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-67HSHXN9DV"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-67HSHXN9DV');
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Online for Fashion, Electronics & More | <?php echo $brandName; ?></title>
    <meta name="description" content="Discover the best deals at <?php echo $brandName; ?>. Shop online for the latest in fashion, electronics, home goods, and more. Enjoy fast delivery & secure payments. Explore our vast collection today!">
    <meta name="keywords" content="online shopping, fashion, electronics, mobiles, home goods, best deals, <?php echo $brandName; ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>" />
    <meta property="og:title" content="Best Deals on Fashion, Electronics & More " />
    <meta property="og:description" content="Discover the best deals at <?php echo $brandName; ?>. Shop online for the latest in fashion, electronics, home goods, and more." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>" />
    <meta property="og:site_name" content="Best Seller" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

   <style>
        body { background-color: #f1f2f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; }
        .main-container { max-width: 1248px; margin: 0 auto; background-color: #fff; }

        .page-header { background-color: #FFFFFF; padding: 8px 16px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.1); }
        .top-bar { display: flex; justify-content: space-between; align-items: center; }
        .logo-container .logo-img { height: 38px; vertical-align: middle; }
        .cart-link a { color: #212121; text-decoration: none; display: flex; align-items: center; position: relative; }
        .cart-icon { width: 24px; height: 24px; }
        .cart-link .badge { position: absolute; top: -8px; right: -10px; }
        .location-and-search { margin-top: 12px; }
        .search-bar { display: flex; align-items: center; background-color: #f0f2f5; border-radius: 8px; padding: 10px 16px; }
        .search-icon { width: 20px; height: 20px; margin-right: 12px; opacity: 0.6; }
        .search-bar input { border: none; outline: none; width: 100%; font-size: 14px; background-color: transparent; }

        .categories-container { padding: 5px; background-color: #ffffff; }
        .categories-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; }
        .category-item a { text-decoration: none; color: #333333; display: flex; flex-direction: column; align-items: center; }
        .category-item img { width: 42px; height: 42px; margin-bottom: 6px; object-fit: contain; }
        .category-label { font-size: 12px; font-weight: 500; text-align: center; line-height: 1.2; }

        .products-section { background-color: #f1f2f4; padding-top: 1px; }
        .mainbody {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1px;
            background-color: #e0e0e0;
        }
        .products { background: white; text-decoration: none; color: black; }
        .productcard { padding: 10px; display: flex; flex-direction: column; height: 100%; }
        .imagecontainer { text-align: center; }
        .productimage { width: 100%; height: 200px; object-fit: contain; }
        .product-info { padding-top: 10px; }
        .product-name { font-size: 14px; color: #212121; line-height: 1.4; height: 40px; overflow: hidden; margin-bottom: 8px; }

        .deal-banner { display: flex; justify-content: space-between; align-items: center; background: #ffffff; border-radius: 12px; padding: 20px; margin: 8px; border: 0.5px solid #e2e8f0; gap: 10px; }
        .deal-left { display: flex; flex-direction: column; align-items: flex-start; flex: 1; position: relative; }
        .deal-title { font-size: 18px; color: #1a73e8; font-weight: 600; margin-bottom: 6px; }
        .deal-timer { display: flex; align-items: center; font-size: 15px; color: #1a73e8; gap: 6px; }
        .deal-timer .material-icons { font-size: 20px; color: #666; }
        .sale-badge { background: linear-gradient(135deg, #0d6efd, #ff4081); color: white; padding: 8px 18px; border-radius: 25px; font-size: 13px; font-weight: bold; border: none; position: relative; overflow: hidden; box-shadow: 0 0 10px rgba(255, 64, 129, 0.5); white-space: nowrap; }
        .sale-badge::before { content: ''; position: absolute; top: 0; left: -75%; width: 50%; height: 100%; background: rgba(255, 255, 255, 0.3); transform: skewX(-25deg); animation: shine 2.2s infinite; }
        @keyframes shine { from { left: -75%; } to { left: 125%; } }
        @media (max-width: 480px) { .deal-banner { flex-direction: row; align-items: center; padding: 10px; gap: 12px; } .deal-title { font-size: 16px; } .deal-timer { font-size: 14px; margin-left: 10%; } .sale-badge { font-size: 12px; padding: 6px 14px; } }

        .price-line { display: flex; align-items: center; flex-wrap: wrap; }
        .selling-price { font-size: 16px; font-weight: 500; color: #212121; }
        .mrp { text-decoration: line-through; color: #878787; font-size: 12px; margin: 0 8px; }
        .discount { font-size: 13px; color: #388e3c; font-weight: 500; }
        .wow-offer { margin-top: 4px; display: flex; align-items: center; }
        .wow-badge { height: 18px; margin-right: 6px; }
        .wow-price { font-size: 14px; font-weight: 500; color: #212121; }
        .offer-text { font-size: 12px; color: #878787; margin-left: 6px; }
        .rating-line { display: flex; align-items: center; margin-top: 6px; }
        .rating-stars { font-size: 14px; color: #26a541; }
        .rating-stars .bi-star { color: #e0e0e0; }
        .fassured-logo-small { height: 16px; margin-left: 10px; }
    </style>
<script async src="https://www.googletagmanager.com/gtag/js?id=G-YQZKNNT3TY"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-YQZKNNT3TY');
</script>
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-17423533065"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-17423533065');
</script>
</head>
<body>

<div class="main-container">
    <header class="page-header">
        <div class="top-bar">
            <!-- NEW WRAPPER FOR MENU BUTTON AND LOGO (FOR MOBILE VIEW) -->
            <div class="d-flex align-items-center">
                <!-- Offcanvas Menu Button for Mobile -->
                <button class="btn p-0 d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sideMenu" aria-label="Open Menu">
                    <i class="bi bi-list" style="color: #212121; font-size: 24px;"></i>
                </button>
                <div class="logo-container">
                   <img src="<?php echo $pwebsite ?>/assets/catogary/svg-image-1.svg" alt="Logo" class="logo-img">
                </div>
            </div>
            <!-- CART LINK (remains on the right) -->
            <div class="cart-link">
                <a href="cart">
                     <svg class="cart-icon" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#212121"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2zm-1.45-5c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.37-.66-.11-1.48-.87-1.48H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.24 17 6.5 17h12v-2H6.5c-.25 0-.42-.21-.38-.45l.93-1.68h7.45z"/></svg>
                   <?php
                        $cart_count = (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? array_sum($_SESSION['cart']) : 0;
                        if ($cart_count > 0) {
                            echo '<span class="badge bg-danger rounded-pill">' . $cart_count . '</span>';
                        }
                    ?>
                </a>
            </div>
        </div>
        <div class="location-and-search">
            <div class="search-bar">
                <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                <input type="text" placeholder="Search for Products">
            </div>
        </div>
    </header>

    <main>
        <section class="main-banner-container p-2">
            <div id="mainCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner rounded-3">
                    <div class="carousel-item active"><img src="<?php echo $pwebsite ?>/assets/catogary/banner1.webp" class="d-block w-100" alt="Banner 1" /></div>
                    <div class="carousel-item"><img src="<?php echo $pwebsite ?>/assets/catogary/banner2.webp" class="d-block w-100" alt="Banner 2" /></div>
                </div>
            </div>
        </section>
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

         <div class="deal-banner">
            <div class="deal-left">
              <div class="deal-title">Deals of the Day</div>
              <div class="deal-timer">
                <span id="timer">05:38</span>
              </div>
            </div>
            <div class="sale-badge">SALE IS LIVE</div>
          </div>

          <script>
            let totalSeconds = 5 * 60 + 38;
            const timerEl = document.getElementById('timer');
            function updateTimer() {
              if (totalSeconds < 0) { totalSeconds = 5 * 60 + 38; }
              let minutes = Math.floor(totalSeconds / 60);
              let seconds = totalSeconds % 60;
              const m = minutes < 10 ? "0" + minutes : minutes;
              const s = seconds < 10 ? "0" + seconds : seconds;
              if(timerEl) { timerEl.textContent = `${m}:${s}`; }
              totalSeconds--;
            }
            setInterval(updateTimer, 1000);
            updateTimer();
          </script>

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
                            <div class="imagecontainer">
                                <img src="<?php echo htmlspecialchars($pwebsite) ?>/assets/uploads/<?php echo htmlspecialchars($fetch_product['image']); ?>" class="productimage" loading="lazy" alt="<?php echo htmlspecialchars($fetch_product['name']); ?>"/>
                            </div>
                            <div class="product-info">
                                <p class="product-name"><?php echo htmlspecialchars($fetch_product['name']); ?></p>
                                <div class="price-line">
                                    <span class="selling-price">₹<?php echo number_format((float)$fetch_product['total']); ?></span>
                                    <del class="mrp">₹<?php echo number_format((float)$fetch_product['price']); ?></del>
                                    <span class="discount"><?php echo htmlspecialchars($fetch_product['discount']); ?>% off</span>
                                </div>
                                <div class="wow-offer">
                                    <img class="wow-badge" src="<?php echo htmlspecialchars($pwebsite) ?>/assets/catogary/wow.webp" alt="WOW Offer">
                                    <span class="wow-price">₹<?php echo number_format($wow_price); ?></span>
                                    <span class="offer-text">with 2 offers</span>
                                </div>
                                <div class="rating-line">
                                    <div class="rating-stars"><?php echo generate_star_rating($star_rating); ?></div>
                                    <img class="fassured-logo-small" src="<?php echo htmlspecialchars($pwebsite) ?>/assets/catogary/assured.png" alt="F-Assured" />
                                </div>
                            </div>
                        </div>
                    </a>
                <?php
                    }
                } else {
                    echo "<p class='text-center w-100 p-4 bg-white'>કોઈ પ્રોડક્ટ મળી નથી.</p>";
                }
                ?>
            </div>

            <div id="loader" style="display:none; text-align:center; padding: 20px;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
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
                console.error('Error loading products:', error);
                isLoading = false;
                loader.style.display = 'none';
            });
    }
    window.addEventListener('scroll', () => {
        if ((window.innerHeight + window.scrollY) >= (document.body.offsetHeight - 500)) {
            loadMoreProducts();
        }
    });
});
</script>
</body>
</html>
<?php
// Include the footer file here.
// This will render the offcanvas menu and the footer content.
// include('../footer.php'); // footer.php pan api/ ma move karo
?>

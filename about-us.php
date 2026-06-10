<?php
session_start();
include('database/connection.php');

// Define cache directory path and constants
define('CACHE_DIR', __DIR__ . '/cache/'); // Make sure this path is correct relative to about-us.php
define('CACHE_EXPIRATION_SETTINGS', 3600); // 1 hour
define('CACHE_EXPIRATION_CATEGORIES', 3600); // 1 hour

// The get_cached_query_result function.
// It's defined here for self-containment of about-us.php,
// assuming it might be accessed directly or without the main index.php context.
if (!function_exists('get_cached_query_result')) {
    function get_cached_query_result($conn, $sql, $types, $params, $cache_file, $cache_time_seconds) {
        if (!is_dir(CACHE_DIR)) {
            @mkdir(CACHE_DIR, 0755, true);
        }

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

        file_put_contents($cache_file, serialize($data));

        return $data;
    }
}


// --- PHP for site settings and categories (needed for header and footer) ---

// Define brandName (default and from settings if available)
$brandName = 'YourStore';
if ($conn) {
    $settings_sql = "SELECT setting_value FROM settings WHERE setting_key = 'brand_name' LIMIT 1";
    $cache_file_settings = CACHE_DIR . 'brand_name.cache';
    $settings_data = get_cached_query_result($conn, $settings_sql, null, [], $cache_file_settings, CACHE_EXPIRATION_SETTINGS);
    if (!empty($settings_data)) {
        $brandName = htmlspecialchars($settings_data[0]['setting_value']);
    }
}


$pwebsite = ''; // Base website URL
if ($conn) {
    $site_sql = "SELECT site FROM credentials LIMIT 1";
    $cache_file_site = CACHE_DIR . 'site_url.cache';
    $site_data = get_cached_query_result($conn, $site_sql, null, [], $cache_file_site, CACHE_EXPIRATION_SETTINGS);
    if (!empty($site_data)) {
        $pwebsite = rtrim($site_data[0]['site'], '/');
    }
}

// Fetch all unique categories for the offcanvas menu and footer
$categories_sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
$cache_file_categories = CACHE_DIR . 'all_categories.cache';
$all_categories_data = get_cached_query_result($conn, $categories_sql, null, [], $cache_file_categories, CACHE_EXPIRATION_CATEGORIES);
$all_categories = [];
foreach ($all_categories_data as $row) {
    $all_categories[] = $row['category'];
}

// Calculate cart count (for header)
$cart_count = (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? array_sum($_SESSION['cart']) : 0;

// Canonical URL generation
$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$canonical_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

?>
<!DOCTYPE html>
<html lang="gu-IN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | <?php echo $brandName; ?></title>
    <meta name="description" content="Learn more about <?php echo $brandName; ?> - our mission, vision, and commitment to quality online shopping.">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>" />
    <meta property="og:title" content="About Us | <?php echo $brandName; ?>" />
    <meta property="og:description" content="Learn more about <?php echo $brandName; ?> - our mission, vision, and commitment to quality online shopping." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>" />
    <meta property="og:site_name" content="<?php echo $brandName; ?>" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700&display=swap" rel="stylesheet">
    <!-- Include your main styles if they are in a separate file, or replicate them here if inline. -->
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

        /* Styles for the offcanvas menu and footer in footer.php */
        /* You might want to move these to a shared CSS file if you use them on many pages */
        .offcanvas-header { background-color: #d81b60; color: white; }
        .offcanvas-body .nav-link, .offcanvas-body .dropdown-item { color: #212121; }
        .offcanvas-body .nav-link i { margin-right: 8px; }

        footer.bg-dark.text-white.mt-5 { margin-top: 0 !important; background-color: #343a40 !important; padding: 20px 0; font-size: 0.9rem; }
        footer a { color: #f8f9fa; text-decoration: none; }
        footer a:hover { color: #d81b60; } /* primary-color from second snippet */
        footer .list-unstyled li { margin-bottom: 5px; }

        .about-content { padding: 30px; line-height: 1.6; }
        .about-content h1, .about-content h2 { color: #333; margin-bottom: 20px; }
        .about-content p { color: #555; margin-bottom: 15px; }
        .about-content ul { list-style-type: disc; margin-left: 20px; margin-bottom: 15px; color: #555; }
        .about-content ul li { margin-bottom: 8px; }
    </style>
</head>
<body>

<div class="main-container">
    <header class="page-header">
        <div class="top-bar">
            <!-- Offcanvas Menu Button for Mobile -->
            <button class="btn p-0 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sideMenu" aria-label="Open Menu">
                <i class="bi bi-list" style="color: #212121; font-size: 24px;"></i>
            </button>
            <div class="logo-container">
               <img src="<?php echo $pwebsite ?>/assets/catogary/svg-image-1.svg" alt="Logo" class="logo-img">
            </div>
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
        <section class="about-content">
            <h1 class="text-center mb-4">About <?php echo $brandName; ?></h1>
            <p>Welcome to <?php echo $brandName; ?>, your premier destination for online shopping! We are dedicated to providing you with the best selection of fashion, electronics, home goods, and much more, all at competitive prices and with exceptional service.</p>

            <h2>Our Mission</h2>
            <p>Our mission is to make online shopping easy, enjoyable, and accessible for everyone. We strive to offer a diverse range of high-quality products, ensuring that you find exactly what you're looking for, whether it's the latest tech gadget, a trendy fashion accessory, or essentials for your home.</p>

            <h2>What We Offer</h2>
            <ul>
                <li>**Vast Product Selection:** Explore our extensive catalog featuring thousands of products across various categories.</li>
                <li>**Quality Assurance:** We carefully select our suppliers and products to ensure high standards of quality.</li>
                <li>**Competitive Prices:** We work hard to bring you the best deals and value for your money.</li>
                <li>**Secure Shopping Experience:** Your security is our priority. We use advanced encryption and secure payment gateways.</li>
                <li>**Fast & Reliable Delivery:** We partner with trusted logistics providers to ensure your orders reach you quickly and safely.</li>
                <li>**Customer Satisfaction:** Our dedicated customer support team is always ready to assist you with any queries or concerns.</li>
            </ul>

            <h2>Our Vision</h2>
            <p>To be the leading online retailer, recognized for our commitment to customer satisfaction, innovation, and a seamless shopping experience. We envision a future where everyone can shop with confidence and convenience from the comfort of their homes.</p>

            <p>Thank you for choosing <?php echo $brandName; ?>. We look forward to serving you!</p>
        </section>
    </main>

    <!-- The why-choose-us section is typically for the homepage.
         If you want it here, you'd need to add its HTML and CSS.
         For an 'About Us' page, it's usually omitted or replaced with specific company info. -->
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Include the footer file here. This will also render the offcanvas menu.
include('footer.php');
?>
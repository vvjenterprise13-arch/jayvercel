<?php
session_start();
include('database/connection.php');

// Define cache directory path and constants
define('CACHE_DIR', __DIR__ . '/cache/'); // Make sure this path is correct relative to shipping-policy.php
define('CACHE_EXPIRATION_SETTINGS', 3600); // 1 hour
define('CACHE_EXPIRATION_CATEGORIES', 3600); // 1 hour

// The get_cached_query_result function.
// It's defined here for self-containment of shipping-policy.php,
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
    <title>Shipping Policy | <?php echo $brandName; ?></title>
    <meta name="description" content="Read the Shipping Policy for <?php echo $brandName; ?> to understand our delivery times, charges, and shipping methods.">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>" />
    <meta property="og:title" content="Shipping Policy | <?php echo $brandName; ?>" />
    <meta property="og:description" content="Read the Shipping Policy for <?php echo $brandName; ?> to understand our delivery times, charges, and shipping methods." />
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
        .offcanvas-header { background-color: #d81b60; color: white; }
        .offcanvas-body .nav-link, .offcanvas-body .dropdown-item { color: #212121; }
        .offcanvas-body .nav-link i { margin-right: 8px; }

        footer.bg-dark.text-white.mt-5 { margin-top: 0 !important; background-color: #343a40 !important; padding: 20px 0; font-size: 0.9rem; }
        footer a { color: #f8f9fa; text-decoration: none; }
        footer a:hover { color: #d81b60; }
        footer .list-unstyled li { margin-bottom: 5px; }

        .shipping-content { padding: 30px; line-height: 1.6; }
        .shipping-content h1, .shipping-content h2, .shipping-content h3 { color: #333; margin-bottom: 20px; margin-top: 30px; }
        .shipping-content p, .shipping-content ul, .shipping-content ol, .shipping-content table { color: #555; margin-bottom: 15px; }
        .shipping-content ul, .shipping-content ol { margin-left: 20px; }
        .shipping-content ul li, .shipping-content ol li { margin-bottom: 8px; }
        .shipping-content table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .shipping-content th, .shipping-content td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .shipping-content th { background-color: #f2f2f2; font-weight: bold; }
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
        <section class="shipping-content">
            <h1 class="text-center mb-4">Shipping Policy for <?php echo $brandName; ?></h1>
            <p class="text-center lead mb-5"><em>Last updated: October 3, 2025</em></p>

            <p>At <?php echo $brandName; ?>, we are committed to delivering your products to you in a timely and efficient manner. This Shipping Policy outlines our shipping practices, delivery times, and costs.</p>

            <h2>1. Shipping Zones</h2>
            <p>We currently ship to addresses within India. For international shipping inquiries, please contact our customer service.</p>

            <h2>2. Processing Time</h2>
            <p>Orders are typically processed within <strong>1-3 business days</strong> (Monday to Friday, excluding public holidays) after payment confirmation. During peak seasons or promotional periods, processing times may be slightly longer.</p>

            <h2>3. Estimated Delivery Times</h2>
            <p>Once your order has been processed and shipped, the estimated delivery times are as follows:</p>
            <ul>
                <li><strong>Standard Shipping:</strong> 5-7 business days</li>
                <li><strong>Express Shipping:</strong> 2-4 business days (where available)</li>
            </ul>
            <p>Please note that these are estimates and actual delivery times may vary due to factors beyond our control, such as courier delays, public holidays, and unforeseen circumstances.</p>

            <h2>4. Shipping Charges</h2>
            <p>Shipping charges are calculated based on the weight of your order, the shipping method selected, and your delivery address. You can view the exact shipping cost at checkout before finalizing your purchase.</p>
            <p>We may offer free standard shipping on orders exceeding a certain value. Any such promotions will be clearly advertised on our website.</p>
            <p><strong>Example Shipping Rates:</strong></p>
            <table>
                <thead>
                    <tr>
                        <th>Order Value</th>
                        <th>Standard Shipping</th>
                        <th>Express Shipping</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Below ₹500</td>
                        <td>₹50</td>
                        <td>₹100</td>
                    </tr>
                    <tr>
                        <td>₹500 - ₹1500</td>
                        <td>₹30</td>
                        <td>₹80</td>
                    </tr>
                    <tr>
                        <td>Above ₹1500</td>
                        <td>Free Shipping</td>
                        <td>₹50</td>
                    </tr>
                </tbody>
            </table>

            <h2>5. Order Tracking</h2>
            <p>Once your order is shipped, you will receive an email notification with a tracking number and a link to track your package's journey. You can also log in to your account on our website to view your order status and tracking information.</p>

            <h2>6. Shipping Address</h2>
            <p>Please ensure that your shipping address is accurate and complete. We are not responsible for delays or non-delivery resulting from incorrect or incomplete address information provided by the customer.</p>

            <h2>7. Damage During Transit</h2>
            <p>We take great care in packaging your items. However, if you receive a damaged product, please contact our customer service immediately (within 24-48 hours of delivery) with photographic evidence of the damage to the item and packaging. We will assist you with a replacement or refund.</p>

            <h2>8. Contact Us</h2>
            <p>If you have any questions or concerns regarding our Shipping Policy, please feel free to contact our customer support team:</p>
            <ul>
                <li>By visiting our contact page: <a href="<?php echo $pwebsite; ?>/contact-us.php"><?php echo $pwebsite; ?>/contact-us.php</a></li>
            </ul>
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Include the footer file here. This will also render the offcanvas menu.
include('footer.php');
?>
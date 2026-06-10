<?php
session_start();
include('database/connection.php');

// Define cache directory path and constants
define('CACHE_DIR', __DIR__ . '/cache/'); // Make sure this path is correct relative to return-policy.php
define('CACHE_EXPIRATION_SETTINGS', 3600); // 1 hour
define('CACHE_EXPIRATION_CATEGORIES', 3600); // 1 hour

// The get_cached_query_result function.
// It's defined here for self-containment of return-policy.php,
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
    <title>Return Policy | <?php echo $brandName; ?></title>
    <meta name="description" content="Read the Return Policy for <?php echo $brandName; ?> to understand our terms for returns, exchanges, and refunds.">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>" />
    <meta property="og:title" content="Return Policy | <?php echo $brandName; ?>" />
    <meta property="og:description" content="Read the Return Policy for <?php echo $brandName; ?> to understand our terms for returns, exchanges, and refunds." />
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

        .return-policy-content { padding: 30px; line-height: 1.6; }
        .return-policy-content h1, .return-policy-content h2, .return-policy-content h3 { color: #333; margin-bottom: 20px; margin-top: 30px; }
        .return-policy-content p, .return-policy-content ul, .return-policy-content ol, .return-policy-content table { color: #555; margin-bottom: 15px; }
        .return-policy-content ul, .return-policy-content ol { margin-left: 20px; }
        .return-policy-content ul li, .return-policy-content ol li { margin-bottom: 8px; }
        .return-policy-content table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .return-policy-content th, .return-policy-content td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .return-policy-content th { background-color: #f2f2f2; font-weight: bold; }
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
        <section class="return-policy-content">
            <h1 class="text-center mb-4">Return Policy for <?php echo $brandName; ?></h1>
            <p class="text-center lead mb-5"><em>Last updated: October 3, 2025</em></p>

            <p>At <?php echo $brandName; ?>, we want you to be completely satisfied with your purchase. If for any reason you are not, we offer a straightforward return and exchange policy. Please read the following terms carefully.</p>

            <h2>1. Eligibility for Returns & Exchanges</h2>
            <p>To be eligible for a return or exchange, your item must meet the following conditions:</p>
            <ul>
                <li>The return request must be initiated within <strong>7 days</strong> of the delivery date.</li>
                <li>The item must be unused, unwashed, and in the same condition that you received it.</li>
                <li>It must be in its original packaging with all tags, labels, and accessories intact.</li>
                <li>Items bought during sale events or with special discounts may be subject to different return conditions, which will be specified at the time of purchase.</li>
                <li>Certain items, such as intimate apparel, customized products, or perishable goods, may be non-returnable for hygiene or other reasons. These will be clearly marked as such on the product page.</li>
            </ul>

            <h2>2. How to Initiate a Return or Exchange</h2>
            <p>To initiate a return or exchange, please follow these steps:</p>
            <ol>
                <li>Log in to your account on our website and go to your "Order History".</li>
                <li>Select the order containing the item(s) you wish to return or exchange.</li>
                <li>Click on the "Return/Exchange" button next to the relevant item(s) and follow the on-screen instructions to submit your request.</li>
                <li>If you checked out as a guest, please contact our customer service directly with your order number and details of the item(s) you wish to return.</li>
                <li>Our customer service team will review your request and provide you with return instructions, including the return shipping address.</li>
            </ol>

            <h2>3. Return Shipping</h2>
            <ul>
                <li>If the return is due to an error on our part (e.g., wrong item shipped, defective product), we will cover the cost of return shipping.</li>
                <li>If the return is due to a change of mind or incorrect size ordered by the customer, the customer will be responsible for the return shipping costs.</li>
                <li>We recommend using a trackable shipping service for returns, as we cannot be responsible for items lost in return transit.</li>
            </ul>

            <h2>4. Refunds</h2>
            <p>Once your returned item is received and inspected, we will send you an email to notify you that we have received your returned item and whether your refund has been approved or rejected.</p>
            <ul>
                <li>If approved, your refund will be processed, and a credit will automatically be applied to your original method of payment within <strong>7-10 business days</strong>.</li>
                <li>Shipping charges (if any) are non-refundable unless the return is due to our error.</li>
            </ul>

            <h2>5. Exchanges</h2>
            <p>If you wish to exchange an item, please follow the return initiation process and specify that you would like an exchange. Exchanges are subject to product availability. If the desired item is not available, a refund will be issued.</p>

            <h2>6. Damaged or Defective Items</h2>
            <p>If you receive a damaged or defective item, please contact us immediately (within 24-48 hours of delivery) with photos of the damaged/defective product and its packaging. We will arrange for a replacement or a full refund, including return shipping costs.</p>

            <h2>7. Cancellations</h2>
            <p>You may cancel your order free of charge within <strong>24 hours</strong> of purchase, provided the order has not yet been shipped. Once an order has been shipped, it falls under our standard return policy.</p>

            <h2>8. Contact Us</h2>
            <p>If you have any questions about our Return Policy, please contact our customer support team:</p>
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
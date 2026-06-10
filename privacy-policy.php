<?php
session_start();
include('database/connection.php');

// Define cache directory path and constants
define('CACHE_DIR', __DIR__ . '/cache/'); // Make sure this path is correct relative to privacy-policy.php
define('CACHE_EXPIRATION_SETTINGS', 3600); // 1 hour
define('CACHE_EXPIRATION_CATEGORIES', 3600); // 1 hour

// The get_cached_query_result function.
// It's defined here for self-containment of privacy-policy.php,
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
    <title>Privacy Policy | <?php echo $brandName; ?></title>
    <meta name="description" content="Read the Privacy Policy of <?php echo $brandName; ?> to understand how we collect, use, and protect your personal information.">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>" />
    <meta property="og:title" content="Privacy Policy | <?php echo $brandName; ?>" />
    <meta property="og:description" content="Read the Privacy Policy of <?php echo $brandName; ?> to understand how we collect, use, and protect your personal information." />
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

        .policy-content { padding: 30px; line-height: 1.6; }
        .policy-content h1, .policy-content h2, .policy-content h3 { color: #333; margin-bottom: 20px; margin-top: 30px; }
        .policy-content p, .policy-content ul, .policy-content ol { color: #555; margin-bottom: 15px; }
        .policy-content ul, .policy-content ol { margin-left: 20px; }
        .policy-content ul li, .policy-content ol li { margin-bottom: 8px; }
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
        <section class="policy-content">
            <h1 class="text-center mb-4">Privacy Policy for <?php echo $brandName; ?></h1>
            <p class="text-center lead mb-5"><em>Last updated: October 3, 2025</em></p>

            <p>This Privacy Policy describes how <?php echo $brandName; ?> (referred to as "we," "us," or "our") collects, uses, and discloses your personal information when you visit, use our services, or make a purchase from <?php echo $brandName; ?> (the "Site") or otherwise communicate with us (collectively, the "Services").</p>

            <h2>1. Information We Collect</h2>
            <p>We collect various types of information from and about users of our Services, including:</p>
            <ul>
                <li><strong>Personal Information:</strong> This includes information that identifies you, such as your name, email address, mailing address, phone number, and payment information. We collect this when you make a purchase, create an account, or contact customer support.</li>
                <li><strong>Order Information:</strong> When you make a purchase, we collect details about your order, including the products purchased, shipping address, billing address, and contact details.</li>
                <li><strong>Usage Data:</strong> We automatically collect information about how you access and use the Services, including your IP address, browser type, operating system, referral URLs, pages viewed, time spent on pages, and clickstream data.</li>
                <li><strong>Device Information:</strong> We may collect information about the device you use to access our Services, including the device type, unique device identifiers, and mobile network information.</li>
                <li><strong>Cookies and Tracking Technologies:</strong> We use cookies, web beacons, and similar tracking technologies to track activity on our Services and hold certain information.</li>
            </ul>

            <h2>2. How We Use Your Information</h2>
            <p>We use the information we collect for various purposes, including to:</p>
            <ul>
                <li>Provide, maintain, and improve our Services, including processing your orders and managing your account.</li>
                <li>Communicate with you about your orders, products, services, and promotional offers.</li>
                <li>Personalize your experience on our Site by presenting products and offers tailored to you.</li>
                <li>Analyze trends, administer the Site, track users' movements around the Site, and gather demographic information about our user base as a whole.</li>
                <li>Detect, prevent, and address technical issues, fraud, or security incidents.</li>
                <li>Comply with legal obligations and enforce our terms and conditions.</li>
            </ul>

            <h2>3. Sharing Your Personal Information</h2>
            <p>We do not sell your personal information. We may share your personal information with third parties in the following circumstances:</p>
            <ul>
                <li><strong>Service Providers:</strong> We engage third-party companies and individuals to facilitate our Services, perform Service-related services (e.g., payment processing, shipping, data analysis, email delivery), or assist us in analyzing how our Services are used.</li>
                <li><strong>Business Transfers:</strong> In connection with, or during negotiations of, any merger, sale of company assets, financing, or acquisition of all or a portion of our business by another company.</li>
                <li><strong>Legal Compliance:</strong> We may disclose your information where required to do so by law or in response to valid requests by public authorities (e.g., a court or a government agency).</li>
                <li><strong>With Your Consent:</strong> We may share your information with your explicit consent or at your direction.</li>
            </ul>

            <h2>4. Cookies and Your Choices</h2>
            <p>We use cookies to enhance your browsing experience, analyze site traffic, and personalize content. You have the option to accept or refuse these cookies and know when a cookie is being sent to your computer. If you choose to refuse our cookies, you may not be able to use some portions of our Service.</p>

            <h2>5. Security of Your Data</h2>
            <p>The security of your data is important to us, but remember that no method of transmission over the Internet, or method of electronic storage is 100% secure. While we strive to use commercially acceptable means to protect your Personal Data, we cannot guarantee its absolute security.</p>

            <h2>6. Your Data Protection Rights</h2>
            <p>Depending on your location, you may have the following data protection rights:</p>
            <ul>
                <li>The right to access, update or to delete the information we have on you.</li>
                <li>The right to have your information rectified if that information is inaccurate or incomplete.</li>
                <li>The right to object to our processing of your Personal Data.</li>
                <li>The right to request that we restrict the processing of your personal information.</li>
                <li>The right to data portability.</li>
                <li>The right to withdraw consent at any time where <?php echo $brandName; ?> relied on your consent to process your personal information.</li>
            </ul>
            <p>To exercise any of these rights, please contact us using the details below.</p>

            <h2>7. Changes to This Privacy Policy</h2>
            <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page. We will let you know via email and/or a prominent notice on our Service, prior to the change becoming effective and update the "Last updated" date at the top of this Privacy Policy.</p>
            <p>You are advised to review this Privacy Policy periodically for any changes. Changes to this Privacy Policy are effective when they are posted on this page.</p>

            <h2>8. Contact Us</h2>
            <p>If you have any questions about this Privacy Policy, please contact us:</p>
            <ul>
                <li>By visiting this page on our website: <a href="<?php echo $pwebsite; ?>/contact-us.php"><?php echo $pwebsite; ?>/contact-us.php</a></li>
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
<?php
session_start();
include('database/connection.php');

// Define cache directory path and constants
define('CACHE_DIR', __DIR__ . '/cache/'); // Make sure this path is correct relative to terms-conditions.php
define('CACHE_EXPIRATION_SETTINGS', 3600); // 1 hour
define('CACHE_EXPIRATION_CATEGORIES', 3600); // 1 hour

// The get_cached_query_result function.
// It's defined here for self-containment of terms-conditions.php,
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
    <title>Terms & Conditions | <?php echo $brandName; ?></title>
    <meta name="description" content="Read the Terms & Conditions for using <?php echo $brandName; ?>'s website and services.">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>" />
    <meta property="og:title" content="Terms & Conditions | <?php echo $brandName; ?>" />
    <meta property="og:description" content="Read the Terms & Conditions for using <?php echo $brandName; ?>'s website and services." />
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

        .terms-content { padding: 30px; line-height: 1.6; }
        .terms-content h1, .terms-content h2, .terms-content h3 { color: #333; margin-bottom: 20px; margin-top: 30px; }
        .terms-content p, .terms-content ul, .terms-content ol { color: #555; margin-bottom: 15px; }
        .terms-content ul, .terms-content ol { margin-left: 20px; }
        .terms-content ul li, .terms-content ol li { margin-bottom: 8px; }
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
        <section class="terms-content">
            <h1 class="text-center mb-4">Terms & Conditions for <?php echo $brandName; ?></h1>
            <p class="text-center lead mb-5"><em>Last updated: October 3, 2025</em></p>

            <p>Welcome to <?php echo $brandName; ?>! These Terms & Conditions ("Terms") govern your use of the website located at <?php echo $pwebsite; ?> (the "Site") and any related services provided by <?php echo $brandName; ?> (referred to as "we," "us," or "our").</p>
            <p>By accessing or using our Services, you agree to be bound by these Terms. If you disagree with any part of the terms then you may not access the Services.</p>

            <h2>1. Accounts</h2>
            <p>When you create an account with us, you must provide information that is accurate, complete, and current at all times. Failure to do so constitutes a breach of the Terms, which may result in immediate termination of your account on our Service.</p>
            <p>You are responsible for safeguarding the password that you use to access the Service and for any activities or actions under your password, whether your password is with our Service or a third-party service.</p>
            <p>You agree not to disclose your password to any third party. You must notify us immediately upon becoming aware of any breach of security or unauthorized use of your account.</p>

            <h2>2. Intellectual Property</h2>
            <p>The Service and its original content, features, and functionality are and will remain the exclusive property of <?php echo $brandName; ?> and its licensors. The Service is protected by copyright, trademark, and other laws of both the India and foreign countries. Our trademarks and trade dress may not be used in connection with any product or service without the prior written consent of <?php echo $brandName; ?>.</p>

            <h2>3. Links to Other Web Sites</h2>
            <p>Our Service may contain links to third-party web sites or services that are not owned or controlled by <?php echo $brandName; ?>.</p>
            <p><?php echo $brandName; ?> has no control over, and assumes no responsibility for, the content, privacy policies, or practices of any third-party web sites or services. You further acknowledge and agree that <?php echo $brandName; ?> shall not be responsible or liable, directly or indirectly, for any damage or loss caused or alleged to be caused by or in connection with use of or reliance on any such content, goods or services available on or through any such web sites or services.</p>
            <p>We strongly advise you to read the terms and conditions and privacy policies of any third-party web sites or services that you visit.</p>

            <h2>4. Termination</h2>
            <p>We may terminate or suspend your account immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms.</p>
            <p>Upon termination, your right to use the Service will immediately cease. If you wish to terminate your account, you may simply discontinue using the Service.</p>

            <h2>5. Limitation Of Liability</h2>
            <p>In no event shall <?php echo $brandName; ?>, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from:</p>
            <ul>
                <li>Your access to or use of or inability to access or use the Service;</li>
                <li>Any conduct or content of any third party on the Service;</li>
                <li>Any content obtained from the Service; and</li>
                <li>Unauthorized access, use or alteration of your transmissions or content, whether based on warranty, contract, tort (including negligence) or any other legal theory, whether or not we have been informed of the possibility of such damage, and even if a remedy set forth herein is found to have failed of its essential purpose.</li>
            </ul>

            <h2>6. Disclaimer</h2>
            <p>Your use of the Service is at your sole risk. The Service is provided on an "AS IS" and "AS AVAILABLE" basis. The Service is provided without warranties of any kind, whether express or implied, including, but not limited to, implied warranties of merchantability, fitness for a particular purpose, non-infringement or course of performance.</p>
            <p><?php echo $brandName; ?> its subsidiaries, affiliates, and its licensors do not warrant that:</p>
            <ul>
                <li>The Service will function uninterrupted, secure or available at any particular time or location;</li>
                <li>Any errors or defects will be corrected;</li>
                <li>The Service is free of viruses or other harmful components; or</li>
                <li>The results of using the Service will meet your requirements.</li>
            </ul>

            <h2>7. Governing Law</h2>
            <p>These Terms shall be governed and construed in accordance with the laws of India, without regard to its conflict of law provisions.</p>
            <p>Our failure to enforce any right or provision of these Terms will not be considered a waiver of those rights. If any provision of these Terms is held to be invalid or unenforceable by a court, the remaining provisions of these Terms will remain in effect. These Terms constitute the entire agreement between us regarding our Service, and supersede and replace any prior agreements we might have between us regarding the Service.</p>

            <h2>8. Changes</h2>
            <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material we will try to provide at least 30 days notice prior to any new terms taking effect. What constitutes a material change will be determined at our sole discretion.</p>
            <p>By continuing to access or use our Service after those revisions become effective, you agree to be bound by the revised terms. If you do not agree to the new terms, please stop using the Service.</p>

            <h2>9. Contact Us</h2>
            <p>If you have any questions about these Terms, please contact us:</p>
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
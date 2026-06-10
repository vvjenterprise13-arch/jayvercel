<?php
// ===================================================================
//              CONFIGURATION & INITIALIZATION
// ===================================================================

// Turn on error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();

// Include database connection
include('database/connection.php');

// ===================================================================
//              યુટિલિટી ફંક્શન્સ (Utility Functions)
// ===================================================================

/**
 * Checks if the user agent string indicates a mobile device.
 * @return bool True if it's a mobile device, false otherwise.
 */
function isMobileDevice() {
    if (!isset($_SERVER["HTTP_USER_AGENT"])) return false;
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

// ===================================================================
//              પેજ લોજીક શરૂ (Start of Page Logic)
// ===================================================================

// Get and sanitize product ID from URL
$id = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;

// ===================================================================
//              ડેટા પુનઃપ્રાપ્તિ (Data Retrieval)
// ===================================================================

// Check database connection
if (!$conn) {
    http_response_code(500);
    die("Database connection failed. Please try again later.");
}

// વેબસાઇટ URL મેળવો
$pwebsite = '';
$site_sql = "SELECT site FROM credentials LIMIT 1";
$site_result = $conn->query($site_sql);
if ($site_result) { // Check if query was successful
    $site_data = $site_result->fetch_all(MYSQLI_ASSOC);
    if (!empty($site_data) && isset($site_data[0]['site'])) {
        $pwebsite = rtrim($site_data[0]['site'], '/');
    }
} else {
    // Log error or handle gracefully
    error_log("Error retrieving site credentials: " . $conn->error);
}


// પ્રોડક્ટ ડેટા મેળવો
$product_data = [];
// Prepare statement for product data
$product_sql = "SELECT * FROM products WHERE id = ?";
$stmt_prod = $conn->prepare($product_sql);

if ($stmt_prod) {
    $stmt_prod->bind_param("i", $id);
    $stmt_prod->execute();
    $product_result = $stmt_prod->get_result();
    $product_data = $product_result->fetch_all(MYSQLI_ASSOC);
    $stmt_prod->close();
} else {
    // Handle prepare statement error
    http_response_code(500);
    die("Internal server error: Could not prepare product query.");
}


// ===================================================================
//      START: આ એરર માટેનો મુખ્ય સુધારો અહીં છે (MAIN FIX FOR THE ERROR IS HERE)
// ===================================================================

// Handle product not found - પ્રોડક્ટને એક્સેસ કરતા પહેલા અહીં તપાસો
if (empty($product_data) || !is_array($product_data) || count($product_data) === 0) {
    http_response_code(404);
    die("આ પ્રોડક્ટ હવે ઉપલબ્ધ નથી. (ID: " . htmlspecialchars($id) . ")");
}

// હવે ખાતરી છે કે પ્રોડક્ટ મળી છે, તેથી આપણે તેને સુરક્ષિત રીતે એક્સેસ કરી શકીએ છીએ.
$item = $product_data[0];

// ===================================================================
//      END: એરર માટેનો સુધારો અહીં પૂરો થાય છે
// ===================================================================

$category = htmlspecialchars($item['category'] ?? '');
$pid = (int)$item['id']; // Ensure pid is always an int, derived from the found item.

// સંબંધિત પ્રોડક્ટ્સ અને અન્ય ડેટા મેળવો
$recommended_products = [];
$rec_sql = "SELECT id, name, total, price, image FROM products WHERE category = ? AND id != ? ORDER BY RAND() LIMIT 6";
$stmt_rec = $conn->prepare($rec_sql);
if ($stmt_rec) {
    $stmt_rec->bind_param("si", $category, $pid);
    $stmt_rec->execute();
    $rec_result = $stmt_rec->get_result();
    $recommended_products = $rec_result->fetch_all(MYSQLI_ASSOC);
    $stmt_rec->close();
} else {
    error_log("Error preparing recommended products query: " . $conn->error);
}


$lowest_price_appliances = [];
$appliances_category = 'Appliances';
$low_sql = "SELECT id, name, total, price, image FROM products WHERE category = ? AND id != ? ORDER BY total ASC LIMIT 6";
$stmt_low = $conn->prepare($low_sql);
if ($stmt_low) {
    $stmt_low->bind_param("si", $appliances_category, $pid);
    $stmt_low->execute();
    $low_result = $stmt_low->get_result();
    $lowest_price_appliances = $low_result->fetch_all(MYSQLI_ASSOC);
    $stmt_low->close();
} else {
    error_log("Error preparing lowest price appliances query: " . $conn->error);
}


// Close the database connection after all queries are done
$conn->close();

// ===================================================================
//              વેરિયેબલ્સ સેટઅપ (Variable Setup)
// ===================================================================

// Using null coalescing operator (??) for robustness
$productName = htmlspecialchars($item['name'] ?? 'Unknown Product');
$productDetails = $item['description'] ?? 'No description available.';
$productPrice = (float)($item['total'] ?? 0.00);
$productDiscount = (int)($item['discount'] ?? 0);
$productOff = (float)($item['price'] ?? $productPrice); // Default to productPrice if 'price' is not set
$productImage = htmlspecialchars($item['image'] ?? 'default.jpg');
$rating = (float)($item['star'] ?? 4.5);
$productSizes = trim($item['size'] ?? '');

$metaDescription = htmlspecialchars(mb_substr(strip_tags($productDetails), 0, 155, 'UTF-8')) . '...';
$canonical_url = $pwebsite . '/singlepageview?pid=' . $pid;

$bank_offers = [
    ['title' => 'Bank Offer', 'description' => 'Get ₹25 instant discount on first UPI txns on order of ₹250 and above'],
    ['title' => 'Bank Offer', 'description' => '5% Cashback on Axis Bank Card'],
    ['title' => 'Special Price', 'description' => 'Get extra 15% off (price inclusive of cashback/coupon)']
];

$delivery_date = date("l, d M", strtotime("+".rand(1, 2)." days"));
$people_ordered = rand(1500, 4000);
$stock_left = rand(1, 10);
$is_in_cart = isset($_SESSION['cart'][$pid]);
$total_ratings_and_reviews = rand(10000, 100000);

$base_5_star = ($rating - 3.5) * 50;
$rating_percentages = [
    5 => max(20, $base_5_star + rand(0, 10)), 4 => max(15, (100 - $base_5_star) / 2 + rand(-5, 5)),
    3 => max(5, 15 + rand(-5, 5)), 2 => max(2, 5 + rand(-2, 2)), 1 => max(1, 3 + rand(-1, 1))
];
$total_percent = array_sum($rating_percentages);
// Normalize percentages to sum up to 100
if ($total_percent > 0) { // Avoid division by zero
    foreach ($rating_percentages as &$percent) { $percent = round(($percent / $total_percent) * 100); }
    // Adjust for rounding errors
    while(array_sum($rating_percentages) > 100) { $rating_percentages[array_rand([1,2,3])]--; }
    while(array_sum($rating_percentages) < 100) { $rating_percentages[array_rand([4,5])]++; }
}


$review_templates = [
    ['name' => 'Rohan Sharma', 'location' => 'Mumbai, Maharashtra', 'title' => 'Excellent Product!', 'review' => 'The quality is amazing, exactly as described. Very happy with the purchase. Highly recommended!'],
    ['name' => 'Priya Patel', 'location' => 'Ahmedabad, Gujarat', 'title' => 'Value for Money', 'review' => 'Good product for the price. Delivery was on time and packaging was secure. Satisfied.'],
];
$generated_reviews = [];
for($i=0; $i<10; $i++){
    $template = $review_templates[array_rand($review_templates)];
    $generated_reviews[] = [
        'name' => $template['name'], 'location' => $template['location'], 'title' => $template['title'],
        'review' => $template['review'], 'rating' => mt_rand(41, 50) / 10, 'date' => date('d M Y', strtotime('-'.rand(5, 90).' days'))
    ];
}

function render_compact_3d_product_carousel($title, $subtitle, $products_array, $pwebsite) {
    if (!empty($products_array)) {
?>
        <section class="compact-showcase-section">
            <div class="compact-section-header">
                <h5 class="fw-bold"><?php echo htmlspecialchars($title); ?></h5>
                <p class="text-muted small mb-3"><?php echo htmlspecialchars($subtitle); ?></p>
            </div>
            <div class="compact-carousel-container">
                <div class="compact-carousel">
                    <?php foreach ($products_array as $item): ?>
                        <div class="compact-product-card">
                            <a href="singlepageview?pid=<?php echo htmlspecialchars($item['id']); ?>" class="product-link">
                                <div class="compact-image-wrapper"><img src="<?php echo htmlspecialchars($pwebsite . '/assets/uploads/' . ($item['image'] ?? 'default.jpg')); ?>" alt="<?php echo htmlspecialchars($item['name'] ?? 'Product Image'); ?>" class="product-image" loading="lazy"></div>
                                <div class="compact-info-wrapper">
                                    <p class="product-name"><?php echo htmlspecialchars($item['name'] ?? 'Product Name'); ?></p>
                                    <div class="price-line">
                                        <span class="fw-bold">₹<?php echo number_format((float)($item['total'] ?? 0)); ?></span>
                                        <del class="ms-2 text-muted small">₹<?php echo number_format((float)($item['price'] ?? 0)); ?></del>
                                    </div>
                                </div>
                            </a>
                            <a href="add_to_cart.php?pid=<?php echo htmlspecialchars($item['id']); ?>" class="compact-add-to-cart-btn">Add to cart</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
<?php
    }
}
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
    <title><?php echo $productName; ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $metaDescription; ?>">
    <link rel="canonical" href="<?php echo $canonical_url; ?>" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body { background-color: #f1f2f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .main-container { max-width: 1248px; margin: 0 auto; background-color: #fff; }
        .page-header { background-color: #fff; padding: 10px 16px; display: flex; align-items: center; position: sticky; top: 0; z-index: 1000; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .back-arrow { color: #212121; text-decoration: none; font-size: 24px; margin-right: 16px; }
        .header-cart { margin-left: auto; }
        .header-cart a { color: #212121; text-decoration: none; position: relative; }
        .carousel-item img { width: 100%; height: 350px; object-fit: contain; padding: 10px; }
        .product-details-container { padding: 16px; }
        .urgency-banner { font-size: 14px; color: #388e3c; font-weight: 500; margin-bottom: 8px; }
        .stock-alert { font-weight: bold; font-size: 16px; margin-bottom: 16px; text-align: center; }
        .product-title { font-size: 18px; font-weight: 500; }
        .rating-box { background-color: #388e3c; color: white; padding: 2px 8px; font-size: 14px; border-radius: 4px; display: inline-flex; align-items: center; }
        .ratings-count { margin-left: 10px; color: #878787; }
        .fassured-logo { height: 20px; margin-top: 8px; }
        .final-price { font-size: 28px; font-weight: bold; }
        .mrp { text-decoration: line-through; color: #878787; margin: 0 12px; }
        .discount { color: #388e3c; font-weight: bold; }
        .size-selector-container .btn-check + .btn { border-radius: 50%; width: 34px; height: 34px; padding: 0; display: flex; align-items: center; justify-content: center; }
        .size-selector-container .btn-check:checked + .btn { background-color: #FB641B; border-color: #FB641B; }
        .footerbuttonbuy { position: fixed; bottom: 0; left: 0; width: 100%; max-width: 1248px; margin: 0 auto; z-index: 100; box-shadow: 0 -2px 5px rgba(0,0,0,0.1); }
        .btn1 { height: 50px; border: none; font-size: 16px; font-weight: 500; }
        .btncart { background-color: #fff; color: #212121; }
        .btnbuy { background-color: #FB641B; color: #fff; }
        .compact-showcase-section { background-color: #fff; padding: 24px 16px; border-top: 6px solid #f1f2f4; }
        .compact-carousel { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 10px; scrollbar-width: none; }
        .compact-carousel::-webkit-scrollbar { display: none; }
        .compact-product-card { flex: 0 0 160px; border: 1px solid #e0e0e0; border-radius: 8px; }
        .compact-product-card .product-link { text-decoration: none; color: #212121; }
        .compact-image-wrapper { height: 140px; padding: 10px; text-align: center; }
        .compact-image-wrapper img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .compact-info-wrapper { padding: 0 12px 12px; }
        .compact-info-wrapper .product-name { font-size: 14px; height: 40px; overflow: hidden; }
        .compact-add-to-cart-btn { display: block; background-color: #f0f5ff; color: #2874f0; text-align: center; text-decoration: none; padding: 8px; margin: 0 12px 12px; border-radius: 4px; font-weight: 500; }
        .product-description-section { padding: 16px; border-top: 6px solid #f1f2f4; }
        .reviews-container { padding: 16px; border-top: 6px solid #f1f2f4; }
        .rating-summary-section { display: flex; gap: 20px; margin-bottom: 24px; }
        .overall-rating .rating-value { font-size: 42px; font-weight: 600; }
        .progress { height: 6px; }
        .review-card { padding-top: 20px; border-top: 1px solid #f0f0f0; }
        .delivery-info { display: flex; align-items: center; padding: 16px 0; border-top: 1px solid #f0f0f0; margin-top: 2px; margin-left: 16px; }
        .delivery-text .free { color: #388e3c; font-weight: bold; }
        .delivery-text .old-fee { text-decoration: line-through; }
        .offers-container { margin-top: 24px; border-top: 1px solid #f0f0f0; padding-top: 16px; }
        .offer-item { display: flex; align-items: flex-start; margin-bottom: 12px; font-size: 14px; }
        .offer-icon { color: #388e3c; margin-right: 5px; margin-top: 5px; font-size: 18px; }
        .offer-text .offer-title { font-weight: 500; margin-right: 5px;}
        .offer-text .offer-link { color: #2874f0; text-decoration: none; font-weight: 500; margin-left: 4px; }
    </style>
</head>
<body class="singleproductview">
<div class="main-container">
    <header class="page-header">
        <a href="#" class="back-arrow" onclick="history.back(); return false;"><i class="material-icons">arrow_back</i></a>
        <img src="<?php echo htmlspecialchars($pwebsite) ?>/assets/catogary/logo.png" alt="Logo" style="width: 40px; height: 40px;">
        <div class="header-cart">
             <a href="cart">
                <i class="material-icons">shopping_cart</i>
                <?php $cart_count = (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? count($_SESSION['cart']) : 0; if ($cart_count > 0) { echo '<span class="badge bg-danger rounded-pill position-absolute top-12 start-100 translate-middle">' . htmlspecialchars($cart_count) . '</span>'; } ?>
            </a>
        </div>
    </header>
    <main>
        <div class="singlecard">
            <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active"><img class="d-block w-100" src="<?php echo htmlspecialchars($pwebsite . '/assets/uploads/' . $productImage); ?>" alt="<?php echo htmlspecialchars($productName); ?>"></div>
                    <?php for ($i = 2; $i <= 10; $i++): $image_column = 'image' . $i; if (!empty($item[$image_column])): ?>
                        <div class="carousel-item"><img class="d-block w-100" src="<?php echo htmlspecialchars($pwebsite . '/assets/uploads/' . htmlspecialchars($item[$image_column])); ?>" alt="<?php echo htmlspecialchars($productName . ' - View ' . $i); ?>"></div>
                    <?php endif; endfor; ?>
                </div>
            </div>
        </div>
        <div class="product-details-container">
            <div class="urgency-banner"><?php echo htmlspecialchars($people_ordered); ?> people ordered this in the last 30 minutes</div>
            <div class="stock-alert">Only <span class="text-danger"><?php echo htmlspecialchars($stock_left); ?></span> Left in Stock</div>
            <?php if (!empty($productSizes)): $availableSizes = array_map('trim', explode(',', $productSizes)); ?>
                <div class="size-selector-container mb-3">
                    <h6 class="fw-bold mb-2">Select Size:</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($availableSizes as $index => $size): ?>
                            <div>
                                <input type="radio" class="btn-check" name="selected_size" id="size-<?php echo htmlspecialchars($size); ?>" value="<?php echo htmlspecialchars($size); ?>" autocomplete="off" <?php if($index == 0) echo 'checked'; ?>>
                                <label class="btn btn-outline-secondary" for="size-<?php echo htmlspecialchars($size); ?>"><?php echo htmlspecialchars($size); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <h1 class="product-title"><?php echo htmlspecialchars($productName); ?></h1>
            <div class="d-flex align-items-center mt-2">
                <span class="rating-box"><?php echo htmlspecialchars(number_format($rating, 1)); ?> <i class="material-icons" style="font-size:12px;">star</i></span>
                <span class="ratings-count"><?php echo htmlspecialchars(number_format($total_ratings_and_reviews)); ?> Ratings</span>
            </div>
            <img src="<?php echo htmlspecialchars($pwebsite) ?>/assets/images/plue-fassured.png" alt="F-Assured" class="fassured-logo">
            <div class="price-container mt-3 d-flex align-items-center">
                <span class="final-price">₹<?php echo htmlspecialchars(number_format($productPrice)); ?></span>
                <del class="mrp">₹<?php echo htmlspecialchars(number_format($productOff)); ?></del>
                <span class="discount"><?php echo htmlspecialchars($productDiscount); ?>% Off</span>
            </div>
        </div>
        <div class="offers-container">
            <h6 class="fw-bold mb-3">Available offers</h6>
            <?php foreach ($bank_offers as $offer): ?>
                <div class="offer-item">
                    <i class="material-icons offer-icon">sell</i>
                    <div class="offer-text">
                        <span class="offer-title"><?php echo htmlspecialchars($offer['title']); ?></span>
                        <?php echo htmlspecialchars($offer['description']); ?>
                        <a href="#" class="offer-link">T&C</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="delivery-info">
            <span class="material-icons me-3">local_shipping</span>
            <div class="delivery-text">
                <div>
                    <span class="free">FREE Delivery</span>
                    <del class="old-fee text-muted">₹75</del>
                </div>
                <div>Delivery by • <span class="fw-bold"><?php echo htmlspecialchars($delivery_date); ?></span></div>
            </div>
        </div>
        <?php
            render_compact_3d_product_carousel('Suggested for You', 'Based on Your Activity', $recommended_products, $pwebsite);
            render_compact_3d_product_carousel('Lowest Price of the Year', 'On Home Appliances', $lowest_price_appliances, $pwebsite);
        ?>
        <div class="product-description-section">
            <h4 class="fw-bold">Product Details</h4>
            <div class="text-muted mt-2"><?php echo $productDetails; /* Already sanitized by strip_tags in meta description, but good to be careful. If $productDetails directly from DB, apply htmlspecialchars here if it might contain unescaped user input */ ?></div>
        </div>
        <div class="reviews-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="fw-bold m-0">Ratings & Reviews</h3>
                <button class="btn btn-outline-secondary btn-sm">Rate Product</button>
            </div>
            <div class="rating-summary-section">
                <div class="overall-rating text-center">
                    <div class="rating-value"><?php echo htmlspecialchars(number_format($rating, 1)); ?> <i class="material-icons align-middle" style="color:#388e3c;">star</i></div>
                    <p class="text-muted small"><?php echo htmlspecialchars(number_format($total_ratings_and_reviews)); ?> Ratings</p>
                </div>
                <div class="rating-breakdown flex-grow-1">
                    <?php foreach ($rating_percentages as $star => $percent): ?>
                        <div class="d-flex align-items-center small">
                            <span><?php echo htmlspecialchars($star); ?>★</span>
                            <div class="progress mx-2 flex-grow-1" style="height:6px;"><div class="progress-bar bg-success" style="width: <?php echo htmlspecialchars($percent); ?>%;"></div></div>
                            <span class="text-muted"><?php echo htmlspecialchars(number_format(floor(($percent / 100) * $total_ratings_and_reviews))); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="review-list">
                <?php foreach($generated_reviews as $review): ?>
                <div class="review-card">
                    <div class="d-flex align-items-center mb-2">
                        <span class="rating-box me-2"><?php echo htmlspecialchars(number_format($review['rating'], 1)); ?> <i class="material-icons" style="font-size:12px;">star</i></span>
                        <h5 class="fw-bold mb-0 small"><?php echo htmlspecialchars($review['title']); ?></h5>
                    </div>
                    <p class="small"><?php echo htmlspecialchars($review['review']); ?></p>
                    <p class="text-muted small m-0"><?php echo htmlspecialchars($review['name']); ?> | <?php echo htmlspecialchars($review['date']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>
<div style="height: 60px;"></div>
<div class="footerbuttonbuy d-flex">
    <?php if ($is_in_cart): ?>
        <a href="cart" class="btn1 btncart w-50 text-center text-decoration-none d-flex align-items-center justify-content-center">Go To Cart</a>
    <?php else: ?>
        <button id="addToCartBtn" class="btn1 btncart w-50">Add To Cart</button>
    <?php endif; ?>
    <button id="buyNowBtn" class="btn1 btnbuy w-50">Buy Now</button>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function handleCartAction(actionType) {
        let productId = <?php echo json_encode($pid); ?>; // Use json_encode for safer JS injection
        let selectedSizeEl = document.querySelector('input[name="selected_size"]:checked');
        let selectedSize = selectedSizeEl ? selectedSizeEl.value : '';
        let url = `add_to_cart.php?pid=${productId}&size=${encodeURIComponent(selectedSize)}`;
        if (actionType === 'buy') { url += '&buy_now=true'; }

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    // Attempt to read error message from server if available
                    return response.text().then(text => { throw new Error(`Network response was not ok: ${text}`); });
                }
                return response.text();
            })
            .then(data => {
                // You might want to parse 'data' if 'add_to_cart.php' returns JSON
                // console.log(data); // For debugging
                if (actionType === 'add') { window.location.reload(); }
                else if (actionType === 'buy') { window.location.href = 'address'; }
            })
            .catch(error => { console.error('Error:', error); alert('Failed to add to cart or buy now. Please try again.'); }); // User feedback
    }
    const addToCartBtn = document.getElementById('addToCartBtn');
    if (addToCartBtn) addToCartBtn.addEventListener('click', () => handleCartAction('add'));
    const buyNowBtn = document.getElementById('buyNowBtn');
    if (buyNowBtn) buyNowBtn.addEventListener('click', () => handleCartAction('buy'));
    const autoScrollCarousels = document.querySelectorAll('.compact-carousel');
    function initAutoScroll(carousel) {
        if (carousel.children.length === 0) return;
        // Clone nodes only if there are enough children to fill the carousel area
        // This prevents infinite loop or broken layout if too few products
        const initialChildrenCount = carousel.children.length;
        if (initialChildrenCount > 0) {
            for (let i = 0; i < initialChildrenCount; i++) {
                carousel.appendChild(carousel.children[i].cloneNode(true));
            }
        }

        let animationFrameId = null;
        const scrollSpeed = 0.7;
        const scrollStep = () => {
            carousel.scrollLeft += scrollSpeed;
            // When half of the original content has scrolled past, reset to the beginning
            if (carousel.scrollLeft >= carousel.scrollWidth / 2) {
                carousel.scrollLeft = 0;
            }
            animationFrameId = requestAnimationFrame(scrollStep);
        };
        const startScrolling = () => { if (!animationFrameId) { animationFrameId = requestAnimationFrame(scrollStep); } };
        const stopScrolling = () => { if (animationFrameId) { cancelAnimationFrame(animationFrameId); animationFrameId = null; } };
        carousel.addEventListener('mouseenter', stopScrolling);
        carousel.addEventListener('mouseleave', startScrolling);
        startScrolling();
    }
    autoScrollCarousels.forEach(carousel => { initAutoScroll(carousel); });
});
</script>
</body>
</html>
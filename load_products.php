<?php
// Vercel માટે સેસન પાથ (જો જરૂર હોય તો)
session_save_path('/tmp');
session_start(); 
include('database/connection.php');

/**
 * કેશિંગ લોજિક વગરનું ડાયરેક્ટ ડેટાબેઝ ક્વેરી ફંક્શન
 */
function get_cached_query_result($conn, $sql, $cache_file, $cache_time_seconds, $types = null, $params = []) {
    // Vercel પર ફાઈલ લખવાનું બંધ કર્યું છે, સીધું ડેટાબેઝમાંથી ડેટા લેશે
    if (!$conn) return [];

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

// વેબસાઇટ URL મેળવો (કેશ વગર)
$pwebsite = '';
if ($conn) {
    $site_sql = "SELECT site FROM credentials LIMIT 1";
    // ફંક્શનના આર્ગ્યુમેન્ટ્સ એવા જ રાખ્યા છે જેથી કોડમાં બીજે ક્યાંય ફેરફાર ન કરવો પડે
    $site_data = get_cached_query_result($conn, $site_sql, '', 0); 
    if (!empty($site_data) && isset($site_data[0]['site'])) {
        $pwebsite = rtrim($site_data[0]['site'], '/');
    }
}

// સ્ટાર રેટિંગ જનરેટ કરવા માટેનું ફંક્શન
function generate_star_rating($rating) {
    $rating = (float)$rating;
    $stars_html = '';
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

    for ($i = 0; $i < $full_stars; $i++) { $stars_html .= '<i class="bi bi-star-fill"></i>'; }
    if ($half_star) { $stars_html .= '<i class="bi bi-star-half"></i>'; }
    for ($i = 0; $i < $empty_stars; $i++) { $stars_html .= '<i class="bi bi-star"></i>'; }
    return $stars_html;
}

// પેજીનેશન સેટઅપ
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$limit = 10;
$offset = ($page - 1) * $limit;

// પ્રોડક્ટ્સ મેળવો અને HTML જનરેટ કરો
if ($conn) {
    $products_sql = "SELECT * FROM products LIMIT ? OFFSET ?";
    $products_array = get_cached_query_result($conn, $products_sql, '', 0, "ii", [$limit, $offset]);

    if (!empty($products_array)) {
        foreach ($products_array as $fetch_product) {
            $wow_price = round((float)$fetch_product['total'] * 0.95);
            $star_rating = (float)($fetch_product['star'] ?? 0);
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
    }
}
?>

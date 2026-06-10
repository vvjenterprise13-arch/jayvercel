<?php
// session_start(); // જો સેશનની જરૂર હોય તો જ રાખો
include('database/connection.php');

/**
 * ડેટાબેઝ ક્વેરીના પરિણામને કેશ કરવા માટેનું મજબૂત અને સુધારેલું ફંક્શન.
 */
function get_cached_query_result($conn, $sql, $cache_file, $cache_time_seconds, $types = null, $params = []) {
    // પગલું 1: જો માન્ય કેશ ફાઈલ હાજર હોય તો તેનો ઉપયોગ કરો
    if (file_exists($cache_file) && filesize($cache_file) > 0 && (time() - filemtime($cache_file)) < $cache_time_seconds) {
        $content = file_get_contents($cache_file);
        $data = @unserialize($content);
        if ($data !== false) {
            return $data;
        }
    }

    // પગલું 2: જો કેશ ન હોય તો ડેટાબેઝમાંથી નવો ડેટા મેળવો
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

    // પગલું 3: ફક્ત ત્યારે જ કેશ કરો જો ડેટા મળ્યો હોય
    if (!empty($data)) {
        if (!is_dir('cache')) {
            @mkdir('cache', 0755, true);
        }
        @file_put_contents($cache_file, serialize($data));
    }
    return $data;
}

// વેબસાઇટ URL મેળવો (આ પેજ પર પણ જરૂરી છે)
$pwebsite = '';
if ($conn) {
    $site_sql = "SELECT site FROM credentials LIMIT 1";
    $cache_file_site = 'cache/site_url.cache';
    $site_data = get_cached_query_result($conn, $site_sql, $cache_file_site, 86400); // 1 day cache
    // ==> [સુધારો અહીં છે] <==
    if (!empty($site_data) && isset($site_data[0]['site'])) {
        $pwebsite = rtrim($site_data[0]['site'], '/');
    }
}

// સ્ટાર રેટિંગ જનરેટ કરવા માટેનું ફંક્શન (આ પેજ પર પણ જરૂરી છે)
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

// પેજીનેશન માટે વેરિયેબલ્સ સેટ કરો
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$limit = 10;
$offset = ($page - 1) * $limit;

// પ્રોડક્ટ્સ મેળવો અને HTML જનરેટ કરો
if ($conn) {
    $cache_file_page = "cache/products_page_{$page}.cache";
    $products_sql = "SELECT * FROM products LIMIT ? OFFSET ?";
    
    // ==> [સુધારો અહીં છે] <==
    // અહીં $types અને $params યોગ્ય રીતે પાસ કરેલા છે.
    $products_array = get_cached_query_result($conn, $products_sql, $cache_file_page, 300, "ii", [$limit, $offset]);

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
    } else {
        // જો આ પેજ પર કોઈ પ્રોડક્ટ ન મળે, તો કોઈ આઉટપુટ ન આપો.
        // આનાથી JavaScript ને ખબર પડશે કે બધા પ્રોડક્ટ્સ લોડ થઈ ગયા છે.
    }
}
?>
<?php
// PHP Error Reporting (can be removed in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('database/connection.php');

// If cart, address, or final amount is not set, the user shouldn't be here.
if (!isset($_SESSION['cart']) || empty($_SESSION['cart']) || !isset($_SESSION['address']) || !isset($_SESSION['final_amount'])) {
    header('Location: order-summary.php'); // Redirect to summary page to calculate price
    exit();
}

// Get the CORRECT final price and format it to two decimal places
// <<< આ લાઈન બદલવામાં આવી છે
$final_payable_amount = number_format($_SESSION['final_amount'], 2, '.', '');

// Get other details needed for payment gateway and Meta Pixel IDs
$pwebsite = ''; 
$upi_id = '';
$pixel_ids = []; // Array to store all available pixel IDs

$creds_result = mysqli_query($conn, "SELECT * from credentials LIMIT 1");
if ($creds_result && $fetch_creds = mysqli_fetch_assoc($creds_result)) {
    $pwebsite = isset($fetch_creds['site']) ? $fetch_creds['site'] : '';
    $upi_id = isset($fetch_creds['access_key']) ? $fetch_creds['access_key'] : '';

    // Loop through possible pixel ID columns and add them to the array if they exist
    for ($i = 1; $i <= 5; $i++) {
        if (!empty($fetch_creds['meta_pixel_id_' . $i])) {
            $pixel_ids[] = $fetch_creds['meta_pixel_id_' . $i];
        }
    }
}

// If an order ID already exists, use it. Otherwise, create a new one.
if (!isset($_SESSION['order_id'])) {
    $order_id = "ORD" . time() . rand(100, 999);
    $_SESSION['order_id'] = $order_id;
} else {
    $order_id = $_SESSION['order_id'];
}

?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
    <title>Payment</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <?php
    // --- META PIXEL CODE ADDED HERE ---
    // Check if there are any pixel IDs to render
    if (!empty($pixel_ids)) {
        foreach ($pixel_ids as $pixel_id) {
            // Sanitize the output
            $pixel_id = htmlspecialchars($pixel_id, ENT_QUOTES, 'UTF-8');
            
            echo "<!-- Meta Pixel Code -->\n";
            echo "<script>\n";
            echo "!function(f,b,e,v,n,t,s)\n";
            echo "{if(f.fbq)return;n=f.fbq=function(){n.callMethod?\n";
            echo "n.callMethod.apply(n,arguments):n.queue.push(arguments)};\n";
            echo "if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';\n";
            echo "n.queue=[];t=b.createElement(e);t.async=!0;\n";
            echo "t.src=v;s=b.getElementsByTagName(e)[0];\n";
            echo "s.parentNode.insertBefore(t,s)}(window, document,'script',\n";
            echo "'https://connect.facebook.net/en_US/fbevents.js');\n";
            echo "fbq('init', '" . $pixel_id . "');\n";
            echo "fbq('track', 'PageView');\n";
            echo "fbq('track', 'InitiateCheckout');\n"; // Added InitiateCheckout event
            echo "</script>\n";
            echo '<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=' . $pixel_id . '&ev=PageView&noscript=1" /></noscript>' . "\n";
            echo "<!-- End Meta Pixel Code -->\n\n";
        }
    }
    ?>
  <style>
    body {
        background-color: #f1f2f4;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        font-size: 13px;
    }
    .main-container { max-width: 600px; margin: 0 auto; background-color: #fff; }

    .page-header { background-color: #fff; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 2px rgba(0,0,0,0.1);  border-bottom: 2px solid #f0f0f0; }
    .header-left { display: flex; align-items: center; gap: 16px; }
    .header-title { font-size: 15px; font-weight: 500; }
    .secure-badge { background-color: #f0f2f5; padding: 6px 10px; border-radius: 4px; font-size: 11px; font-weight: 500; display: flex; align-items: center; gap: 6px; }
    .secure-badge .by-brand { color: #878787; font-size: 10px; }

    .payment-options-container { background-color: #fff; }
    .payment-accordion .accordion-item { border: none; border-radius: 0; }
    .payment-accordion .accordion-button { background-color: #fff; font-weight: 500; font-size: 15px; box-shadow: none !important; padding: 16px; }
    .payment-accordion .accordion-button:not(.collapsed) { color: #212121; background-color: #fff; }
    .payment-accordion .accordion-button::after { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23212121'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e"); }
    .payment-accordion .accordion-body { background-color: #fff; padding: 0 16px 16px 16px; }
    .payment-option { display: flex; align-items: center; padding: 16px 0; border-top: 1px solid #f0f0f0; }
    .payment-option label { width: 100%; cursor: pointer; display: flex; align-items: center; }
    .payment-option .form-check-input { border-radius: 50%; width: 20px; height: 20px; border: 2px solid #c7c7c7; margin-top: 0; }
    .payment-option .form-check-input:checked { background-color: #2874f0; border-color: #2874f0; box-shadow: 0 0 0 3px #fff, 0 0 0 4px #2874f0; }
    .payment-info { margin-left: 16px; flex-grow: 1; }
    .payment-info .price { font-weight: 500; }
    .payment-info .discount-text { color: #388e3c; font-size: 11px; font-weight: 500; }
    .payment-logo { height: 40px; }
    .text-purple-600 { color: #581c87; }

    .offer-banner { background-color: #f0f5ff; border-radius: 8px; padding: 12px; display: flex; align-items: center; gap: 12px; margin: 10px; }
    .offer-banner i { color: #2874f0; }
    .offer-banner p { font-size: 12px; margin: 0; }

    .price-details-card { background-color: #fff; padding: 16px; border-top: 8px solid #f1f2f4; }
    .price-details-row { display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 13px; }
    .price-details-row span:first-child { color: #212121; }
    .total-payable-row { font-weight: bold; border-top: 1px dashed #e0e0e0; padding-top: 16px; }

    .page-footer { background: #fff !important; border-top: 1px solid #e0e0e0 !important; position: fixed; bottom: 0; width: 100%; max-width: 600px; padding: 12px 16px; box-shadow: 0 -2px 5px rgba(0,0,0,0.1); }
    .footer-price { font-size: 17px; font-weight: bold; }
    .continue-btn {
        width: 50%;
        background-color: #fb641b; 
        color: #fff;
        border: none;
        padding: 12px;
        font-size: 15px;
        font-weight: 500;
        border-radius: 4px;
    }
    .continue-btn:focus, .continue-btn:active {
        background-color: #fb641b !important;
        color: #fff !important;
        outline: none !important;
        box-shadow: none !important;
        border: none;
    }
    .lock-icon { width: 14px; height: 14px; }
     .site-footer {
        background-color: #172337; color: #fff; font-size: 12px;
        padding: 40px 15px 0; line-height: 1.5; text-align: left;
    }
    .footer-main-container {
        display: flex; flex-wrap: wrap; justify-content: space-between;
        max-width: 1200px; margin: 0 auto; gap: 20px;
    }
    .footer-column { flex: 1; min-width: 150px; margin-bottom: 20px; }
    .footer-column h4 { color: #878787; font-size: 12px; font-weight: 500; margin-bottom: 12px; text-transform: uppercase; }
    .footer-column ul { list-style: none; padding: 0; margin: 0; }
    .footer-column ul li { margin-bottom: 9px; }
    .footer-column ul li a { color: #fff; text-decoration: none; }
    .footer-bottom-bar { border-top: 1px solid #454d5e; padding: 25px 0; margin-top: 20px; font-size: 13px; }
    .bottom-bar-container { display: flex; justify-content: space-evenly; align-items: center; flex-wrap: wrap; gap: 15px 20px; }
    .bottom-bar-link { color: #fff; text-decoration: none; display: flex; align-items: center; gap: 8px; }
    
    /* ========== LOADER CSS ADDED HERE ========== */
    .loader-overlay {
        position: fixed;
        inset: 0;
        background-color: rgba(255, 255, 255, 0.95);
        display: none; /* Initially hidden */
        align-items: center;
        justify-content: center;
        z-index: 9999;
        flex-direction: column;
        gap: 15px;
    }
    .loader {
        width: 48px;
        height: 48px;
        border: 5px solid #cccccc;
        border-bottom-color: #2874f0;
        border-radius: 50%;
        display: inline-block;
        box-sizing: border-box;
        animation: rotation 1s linear infinite;
    }
    .loader-text {
        font-size: 14px;
        font-weight: 500;
        color: #555;
    }
    @keyframes rotation {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    } 

    /* === ફેરફાર અહીંથી શરૂ === */
    /* ક્લોઝ બટન માટે નવી CSS */
    .close-loader-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        background: #e0e0e0;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #555;
        cursor: pointer;
        z-index: 10000; /* ખાતરી કરવા માટે કે તે લોડરની ઉપર છે */
    }
    .close-loader-btn:hover {
        background: #d0d0d0;
    }
    /* === ફેરફાર અહીં સમાપ્ત === */

</style>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-YQZKNNT3TY"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-YQZKNNT3TY');
</script>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-17423533065"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-17423533065');
</script>
</head>
<body>

<!-- === ફેરફાર અહીંથી શરૂ === -->
<!-- LOADER OVERLAY માં ક્લોઝ બટન ઉમેર્યું -->
<div class="loader-overlay" id="loaderOverlay">
    <button id="closeLoaderBtn" class="close-loader-btn" aria-label="Close">
        <i class="bi bi-x-lg"></i>
    </button>
    <div class="loader"></div>
    <div class="loader-text">Processing your payment...</div>
    <div class="loader-text small text-muted">Please complete the payment in your UPI app.</div>
</div>
<!-- === ફેરફાર અહીં સમાપ્ત === -->


<div class="main-container">
    <header class="page-header">
        <div class="header-left">
            <a href="order-summary.php" class="text-dark"><i class="bi bi-arrow-left fs-5"></i></a>
            <div class="header-title">Payment</div>
        </div>
       <div class="secure-badge">
           <img src="assets/images/lock.png" alt="Percent Icon" class="fs-4" style="width: 20px; height: 20px;">
            <div>
                100% Secure <br><span class="by-brand">by Flipkart</span>
            </div>
        </div>
    </header>

    <main style="padding-bottom: 10px;"> <!-- Added more padding bottom for footer -->
        <div class="payment-options-container">
            <div class="accordion payment-accordion" id="paymentAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            <img src="https://i.ibb.co/9mXRgLBm/pay.webp" height="24" class="me-3" alt="UPI"> UPI Payment
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#paymentAccordion">
                        <div class="accordion-body">
                            <p class="small fw-bold text-muted">Choose Payment Method:</p>
                            <!-- PhonePe -->
                            <div class="payment-option">
                                <label><input class="form-check-input" type="radio" name="paymentMethod" value="phonepe" checked><div class="payment-info"><div class="price">₹<?php echo number_format($final_payable_amount); ?> | PhonePe</div><div class="discount-text text-purple-600">20% Extra Discount By PhonePe</div></div><img src="assets/images/phonepe.svg" class="payment-logo" alt="PhonePe"></label>
                            </div>
                            <!-- Google Pay -->
                         
                            <!-- Paytm -->
                            <div class="payment-option">
                                 <label><input class="form-check-input" type="radio" name="paymentMethod" value="paytm"><div class="payment-info"><div class="price">₹<?php echo number_format($final_payable_amount); ?> | Paytm</div><div class="discount-text">20% Extra Discount By Paytm</div></div><img src="assets/images/paytm_icon.svg" class="payment-logo" alt="Paytm"></label>
                            </div>
                             <!-- Pay With QR -->
                            <div class="payment-option">
                                 <label><input class="form-check-input" type="radio" name="paymentMethod" value="upi"><div class="payment-info"><div class="price">₹<?php echo number_format($final_payable_amount); ?> | Pay With Qr </div></div><img src="assets/images/qr.png" class="payment-logo" alt="QR"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="offer-banner">
            <img src="https://i.ibb.co/pBzVVNLh/cs.webp" alt="Percent Icon" class="fs-4" style="width: 60px; height: 60px;">
            <p><b>Get ₹50 Cashback on Orders Above ₹500!</b><br>
            Spend more than ₹500 and receive a guaranteed ₹250 cashback. Offer valid for a limited time only!</p>
        </div>

        <div class="price-details-card">
             <h6 class="fw-bold mb-3">Price Details</h6>
            <div class="price-details-row">
                <span>Price</span>
                <span>₹<?php echo number_format($final_payable_amount); ?></span>
            </div>
             <div class="price-details-row">
                <span>Delivery Charges</span>
                <span class="text-success">FREE</span>
            </div>
            <div class="price-details-row total-payable-row fs-6">
                <span>Total Amount</span>
                <span>₹<?php echo number_format($final_payable_amount); ?></span>
            </div>
        </div>
    </main>

    <footer class="page-footer">
        <div class="d-flex justify-content-between align-items-center">
            <span class="footer-price">₹<?php echo number_format($final_payable_amount); ?></span>
            <button id="continueBtn" class="btn continue-btn">Continue</button>
        </div>
    </footer>
     <footer class="site-footer">
        <div class="footer-main-container">
            <div class="footer-column">
                <h4>About</h4>
                <ul><li><a href="#">Contact Us</a></li><li><a href="#">About Us</a></li><li><a href="#">Careers</a></li></ul>
            </div>
            <div class="footer-column">
                <h4>Help</h4>
                <ul><li><a href="#">Payments</a></li><li><a href="#">Shipping</a></li><li><a href="#">Cancellation & Returns</a></li><li><a href="#">FAQ</a></li></ul>
            </div>
            <div class="footer-column">
                <h4>Policy</h4>
                <ul><li><a href="#">Return Policy</a></li><li><a href="#">Terms Of Use</a></li><li><a href="#">Security</a></li><li><a href="#">Privacy</a></li></ul>
            </div>
            <div class="footer-column">
                <h4>Social</h4>
                <ul><li><a href="#">Facebook</a></li><li><a href="#">Twitter</a></li><li><a href="#">YouTube</a></li></ul>
            </div>
        </div>
         <div class="text-center p-3" style="font-size: 13px; background-color: var(--footer-bg);">
           <div class="payment-methods"><img src="https://static-assets-web.flixcart.com/batman-returns/batman-returns/p/images/payment-method-c454fb.svg" alt="Payment Methods" style="width: 100%; height: auto;" loading="lazy"></div>
        </div>
        <div class="footer-bottom-bar">
            <div class="bottom-bar-container">
                <a href="#" class="bottom-bar-link"><span>🏬</span> Become a Seller</a>
                <a href="#" class="bottom-bar-link"><span>✨</span> Advertise</a>
                <a href="#" class="bottom-bar-link"><span>🎁</span> Gift Cards</a>
                <a href="#" class="bottom-bar-link"><span>❔</span> Help Center</a>
            </div>
        </div>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* === ફેરફાર અહીંથી શરૂ === */
// જાવાસ્ક્રિપ્ટમાં ફેરફારો
document.addEventListener('DOMContentLoaded', function () {
    const continueBtn = document.getElementById('continueBtn');
    const loaderOverlay = document.getElementById('loaderOverlay');
    const closeLoaderBtn = document.getElementById('closeLoaderBtn'); // ક્લોઝ બટન મેળવો
    let redirectTimer; // ટાઈમર ID સ્ટોર કરવા માટે વેરિએબલ

    continueBtn.addEventListener('click', function () {
        const selectedMethod = document.querySelector('input[name="paymentMethod"]:checked');
        if (selectedMethod) {
            // લોડર બતાવો
            loaderOverlay.style.display = 'flex';
            
            // પેમેન્ટ શરૂ કરો
            payNow(selectedMethod.value);

            // જો કોઈ જૂનો ટાઈમર ચાલુ હોય તો તેને રદ કરો
            clearTimeout(redirectTimer); 

            // 15 સેકન્ડ પછી થેન્ક યુ પેજ પર રીડાયરેક્ટ કરવા માટે ટાઈમર સેટ કરો
            // અને ટાઈમર ID ને વેરિએબલમાં સ્ટોર કરો
            redirectTimer = setTimeout(function() {
                window.location.href = 'thankyou.php';
            }, 15000); // 15000 મિલિસેકન્ડ = 15 સેકન્ડ

        } else {
            alert('Please select a payment method.');
        }
    });

    // ક્લોઝ બટન માટે ક્લિક ઇવેન્ટ ઉમેરો
    closeLoaderBtn.addEventListener('click', function() {
        // લોડર છુપાવો
        loaderOverlay.style.display = 'none';
        
        // અત્યંત મહત્વપૂર્ણ: રીડાયરેક્ટ ટાઈમરને રદ કરો
        clearTimeout(redirectTimer);
    });

    function payNow(payType) {
        let upi_address = '<?php echo $upi_id; ?>';
        let amt = '<?php echo $final_payable_amount; ?>';
        let site_name = 'JamaFashion';
        let order_id = '<?php echo $order_id; ?>';
        let redirect_url = '';

        switch (payType) {
            case 'gpay':
                redirect_url = `tez://upi/pay?pa=${upi_address}&pn=Online%20Store&tn=Order_Id_${order_id}&am=${amt}&tr=${order_id}&mc=8931&cu=INR&tn=Verified%20Seller`;
                break;

            case 'phonepe':
                redirect_url = `phonepe://pay?ver=01&mode=19&pa=${upi_address}&pn=Verified%20Seller&tr=${order_id}&cu=INR&mc=4215&qrMedium=04&tn=${order_id}&am=${amt}`;
                break;

            case 'paytm':
                redirect_url = `paytmmp://cash_wallet?pa=${upi_address}&pn=Moomin Ashraf&mc=3526&tr=&am=${amt}&cu=INR&tn=Online Shopping&url=&mode=02&purpose=00&orgid=37567&sign=MEYCIQC41mu+HMffQXue6e9sMxOMYEkDgPPDIL4Kw2jV2U3eYQIhAP1Ot6G4dVo0xuz26kaAWjiZXWhnxb7ve+lUFOtLIwzm&featuretype=money_transfer`;
                break;
            
            case 'upi':
                redirect_url = `https://upi2qr.in/pay?name=shopping+Payment&upiId=${upi_address}&amount=${amt}&description=Pay_To_shopping_${order_id}`;
                break;

            default:
                alert("Invalid payment method selected.");
                loaderOverlay.style.display = 'none'; // જો અમાન્ય હોય તો લોડર છુપાવો
                return;
        }

        // એપ્લિકેશન પર રીડાયરેક્ટ કરી રહ્યું છે
        window.location.href = redirect_url;
    }
});
/* === ફેરફાર અહીં સમાપ્ત === */
</script>

</body>
</html>
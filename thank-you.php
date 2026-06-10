<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - Thank You!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f1f2f4;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }
        .thankyou-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
            padding: 40px 30px;
        }
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            width: 100px;
            height: 100px;
            line-height: 100px;
            border-radius: 50%;
            background-color: #e9f7eb;
            margin: 0 auto 20px;
        }
        .thankyou-title {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        .thankyou-subtitle {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 30px;
        }
        .order-details {
            text-align: left;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            background-color: #fafafa;
        }
        .order-details p {
            font-size: 1rem;
            color: #333;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
        }
        .order-details p strong {
            color: #000;
        }
        .btn-continue {
            background-color: #fb641b;
            color: #fff;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .btn-continue:hover {
            background-color: #e0540c;
            color: #fff;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="thankyou-container">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        <h1 class="thankyou-title">Thank You!</h1>
        <p class="thankyou-subtitle">Your order has been placed successfully.</p>
        
        <div class="order-details">
            <h5 class="text-center mb-4 fw-bold">Your Order Details</h5>
            <p><span>Order ID:</span> <strong>N/A</strong></p>
            <p><span>Payment ID:</span> <strong>N/A</strong></p>
            <p><span>Order Status:</span> <strong class="text-success">Confirmed</strong></p>
        </div>

        <p class="text-muted small mb-4">You will receive an order confirmation email with details shortly.</p>

        <a href="index" class="btn btn-continue">Continue Shopping</a>
    </div>
</div>

</body>
</html>
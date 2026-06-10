<?php
$reason = isset($_GET['reason']) ? htmlspecialchars($_GET['reason']) : 'An unknown error occurred.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f1f2f4; }
        .failed-container { max-width: 600px; margin: 40px auto; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); text-align: center; padding: 40px 30px; }
        .fail-icon { font-size: 5rem; color: #dc3545; }
        .failed-title { font-size: 2rem; font-weight: 700; color: #333; margin-top: 20px; }
        .btn-retry { background-color: #fb641b; color: #fff; }
        .btn-retry:hover { color: #fff; background-color: #e0540c; }
    </style>
</head>
<body>
<div class="container">
    <div class="failed-container">
        <div class="fail-icon"><i class="fas fa-times-circle"></i></div>
        <h1 class="failed-title">Payment Failed</h1>
        <p class="text-muted mt-3">We are sorry, your transaction could not be completed.</p>
        <div class="alert alert-danger mt-4">
            <strong>Reason:</strong> <?php echo $reason; ?>
        </div>
        <p class="mt-4">Please try again or use a different payment method.</p>
        <a href="/order-summary.php" class="btn btn-retry mt-3 fw-bold">Try Again</a>
    </div>
</div>
</body>
</html>
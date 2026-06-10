<?php
session_start();
include('database/connection.php');


if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart');
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
   
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, trim($_POST['name'])) : '';
    $number = isset($_POST['number']) ? mysqli_real_escape_string($conn, trim($_POST['number'])) : '';
    $pincode = isset($_POST['pin']) ? mysqli_real_escape_string($conn, trim($_POST['pin'])) : '';
    $state = isset($_POST['state']) ? mysqli_real_escape_string($conn, trim($_POST['state'])) : '';
    $city = isset($_POST['city']) ? mysqli_real_escape_string($conn, trim($_POST['city'])) : '';
    $flat = isset($_POST['flat']) ? mysqli_real_escape_string($conn, trim($_POST['flat'])) : '';
    $area = isset($_POST['area']) ? mysqli_real_escape_string($conn, trim($_POST['area'])) : '';
    $address_type = isset($_POST['address_type']) ? mysqli_real_escape_string($conn, trim($_POST['address_type'])) : 'Home'; 
 
    $address = [
        'name' => $name,
        'number' => $number,
        'pincode' => $pincode,
        'state' => $state,
        'city' => $city,
        'flat' => $flat,
        'area' => $area,
        'address_type' => $address_type,
    ];

  
    $_SESSION['address'] = $address;

   
    header('Location: order-summary');
    exit(); 
}


$saved_address = isset($_SESSION['address']) ? $_SESSION['address'] : null;
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
    <title>Add Delivery Address</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,minimum-scale=1,user-scalable=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f1f2f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; }
        .main-content { background-color: #fff; }
        .page-header { background-color: #fff; padding: 12px 16px; display: flex; align-items: center; gap: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100; }
        .header-title { font-size: 18px; font-weight: 500; margin: 0; }
        .progress-stepper { display: flex; align-items: center; justify-content: space-between; padding: 5px 5px; background-color: #fff; border-bottom: 1px solid #f0f0f0; }
        .step { display: flex; flex-direction: column; align-items: center; position: relative; flex-grow: 1; text-align: center; }
        .step-circle { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; margin-bottom: 5px; border: 1px solid #dbdbdb; color: #dbdbdb; background-color: #fff; }
        .step-label { font-size: 12px; color: #878787; }
        .step.active .step-circle { background-color: #2874f0; color: #fff; border-color: #2874f0; }
        .step.active .step-label { color: #2874f0; font-weight: 500; }
        .step:not(:last-child)::after { content: ''; position: absolute; top: 12px; left: 50%; width: 100%; height: 1px; background-color: #dbdbdb; z-index: -1; transform: translateX(12px); }
        .form-section { padding: 16px; padding-bottom: 100px; }
        .form-floating > .form-control, .form-floating > .form-select { height: 50px; padding-top: 1.3rem; padding-bottom: 0.5rem; font-size: 14px; }
        .form-floating > label { padding: 0.8rem 0.75rem; font-size: 14px; color: #6c757d; }
        .form-control:focus, .form-select:focus { border-color: #ced4da; box-shadow: none; }
        .address-type-container { margin-top: 10px; margin-bottom: 20px; }
        .address-type-label { font-size: 14px; color: #878787; margin-bottom: 12px; }
        .address-type-options { display: flex; gap: 10px; }
        .address-type-options input[type="radio"] { display: none; }
        .address-type-btn { border: 1px solid #dcdcdc; padding: 8px 16px; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px; transition: all 0.2s ease; }
        .address-type-options input[type="radio"]:checked + .address-type-btn { border-color: #2874f0; background-color: #f0f5ff; font-weight: 500; color: #2874f0; }
        .page-footer { background: #fff !important; border-top: 1px solid #e0e0e0 !important; position: fixed; bottom: 0; width: 100%; left: 0; padding: 12px 16px; }
        .save-btn { width: 100%; background-color: #fb641b; color: white; border: none; padding: 14px; font-size: 16px; font-weight: 500; border-radius: 4px; }
    </style>
</head>
<body>
    <header class="page-header">
        <a href="cart" class="text-dark"><i class="bi bi-arrow-left fs-4"></i></a>
        <h4 class="header-title">Address</h4>
    </header>

    <main class="main-content">
        <div class="progress-stepper">
            <div class="step active"><div class="step-circle">1</div><div class="step-label">Address</div></div>
            <div class="step"><div class="step-circle">2</div><div class="step-label">Order Summary</div></div>
            <div class="step"><div class="step-circle">3</div><div class="step-label">Payment</div></div>
        </div>

        <section class="form-section">
            <form method="POST" action="address">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="name" name="name" placeholder="Full Name" value="<?php echo isset($saved_address['name']) ? htmlspecialchars($saved_address['name']) : ''; ?>">
                    <label for="name">Full Name</label>
                </div>
                
               <div class="form-floating mb-3">
  <input 
      type="tel" 
      class="form-control" 
      id="number" 
      name="number" 
      placeholder="Mobile number" 
      pattern="\d{10}" 
      minlength="10" 
      maxlength="10" 
      required
  >
  <label for="number">+91 Mobile number</label>
</div>

<script>
  const numberInput = document.getElementById('number');

  numberInput.addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 10);

    if (this.value.length !== 10) {
      this.setCustomValidity("Please enter 10 digit phone number");
    } else {
      this.setCustomValidity(""); // clear error
    }
  });
</script>
                
                <div class="form-floating mb-3">
                    <input type="tel" class="form-control" id="pincode" name="pin" placeholder="Pincode" pattern="[0-9]{6}" value="<?php echo isset($saved_address['pincode']) ? htmlspecialchars($saved_address['pincode']) : ''; ?>">
                    <label for="pincode">Pincode</label>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="city" name="city" placeholder="City" value="<?php echo isset($saved_address['city']) ? htmlspecialchars($saved_address['city']) : ''; ?>">
                            <label for="city">City</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating">
                            <select class="form-select" id="state" name="state">
                                <option value="" disabled <?php echo !isset($saved_address['state']) ? 'selected' : ''; ?>>Select State</option>
                                <?php
                                $states = ["Andhra Pradesh", "Arunachal Pradesh", "Assam", "Bihar", "Chhattisgarh", "Goa", "Gujarat", "Haryana", "Himachal Pradesh", "Jammu & Kashmir", "Jharkhand", "Karnataka", "Kerala", "Madhya Pradesh", "Maharashtra", "Manipur", "Meghalaya", "Mizoram", "Nagaland", "Odisha", "Punjab", "Rajasthan", "Sikkim", "Tamil Nadu", "Telangana", "Tripura", "Uttarakhand", "Uttar Pradesh", "West Bengal"];
                                foreach ($states as $state_name) {
                                    $selected = (isset($saved_address['state']) && $saved_address['state'] == $state_name) ? 'selected' : '';
                                    echo "<option value='$state_name' $selected>$state_name</option>";
                                }
                                ?>
                            </select>
                            <label for="state">State</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-floating mb-3">
                     <input type="text" class="form-control" id="flat" name="flat" placeholder="House No., Building Name" value="<?php echo isset($saved_address['flat']) ? htmlspecialchars($saved_address['flat']) : ''; ?>">
                     <label for="flat">House No., Building Name</label>
                </div>
               
                <div class="form-floating mb-3">
                     <input type="text" class="form-control" id="area" name="area" placeholder="Road name, Area, Colony" value="<?php echo isset($saved_address['area']) ? htmlspecialchars($saved_address['area']) : ''; ?>">
                     <label for="area">Road name, Area, Colony</label>
                </div>
                
                <div class="address-type-container">
                    <p class="address-type-label">Type of address</p>
                    <div class="address-type-options">
                        <input type="radio" id="home_address" name="address_type" value="Home" <?php echo (!isset($saved_address['address_type']) || $saved_address['address_type'] == 'Home') ? 'checked' : ''; ?>>
                        <label for="home_address" class="address-type-btn"><i class="bi bi-house-door-fill"></i> Home</label>
                        <input type="radio" id="work_address" name="address_type" value="Work" <?php echo (isset($saved_address['address_type']) && $saved_address['address_type'] == 'Work') ? 'checked' : ''; ?>>
                        <label for="work_address" class="address-type-btn"><i class="bi bi-building-fill"></i> Work</label>
                    </div>
                </div>
                
                <footer class="page-footer">
                    <button type="submit" class="save-btn">Save and Deliver Here</button>
                </footer>
            </form>
        </section>
    </main>
</body>
</html>
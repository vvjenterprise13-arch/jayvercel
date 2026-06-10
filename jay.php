<?php
session_start();

// ==========================================
// ૧. સેટિંગ્સ (GitHub સ્ક્રીનશોટ મુજબ dynamic)
// ==========================================
$admin_pass_panel = "Jay@5228"; // પેનલ લોગિન પાસવર્ડ

// GitHub ના કનેક્શન મુજબ Environment variables સેટ કર્યા છે
$db_host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';
$db_user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: 'VMtftOTfdRbsDzULRKKVeGE1XACVxXPI';

// તમારા ડેટાબેઝ લિસ્ટ
$databases = [
    'railway'
];

// pgsite પાથ મેપિંગ
$pgsite_paths = [
    'railway'   => 'secure/'
];

if (isset($_GET['logout'])) { session_destroy(); header("Location: manage.php"); exit; }
if (isset($_POST['login'])) { if ($_POST['pass'] == $admin_pass_panel) $_SESSION['auth_db'] = true; else $error = "ખોટો પાસવર્ડ!"; }
if (!isset($_SESSION['auth_db'])) {
?>
<!DOCTYPE html><html><head><title>Login</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-dark d-flex align-items-center" style="height: 100vh;"><div class="card mx-auto p-4" style="width: 320px;">
<h5 class="text-center">Admin Login</h5><form method="POST"><input type="password" name="pass" class="form-control mb-3" placeholder="Password"><button type="submit" name="login" class="btn btn-primary w-100">Login</button></form></div></body></html>
<?php exit; }

$current_db = isset($_GET['db']) && in_array($_GET['db'], $databases) ? $_GET['db'] : $databases[0];

// PDO કનેક્શન ફંક્શન
function getPDO($db, $host, $user, $pass, $port) {
    return new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

$pdo = getPDO($current_db, $db_host, $db_user, $db_pass, $db_port);
$msg = "";

// ==========================================
// ૨. અપડેટ અને સિંક લોજિક
// ==========================================
if (isset($_POST['update_record'])) {
    $id = $_POST['id'];

    // ઇનપુટ માંથી ડોમેન ખેંચો
    $url_input = $_POST['pgsite'];
    $parsed = parse_url($url_input);
    $base_domain = (isset($parsed['scheme']) ? $parsed['scheme'] . "://" : "https://") . ($parsed['host'] ?? '');
    $base_domain = rtrim($base_domain, '/');

    // ૧. કરંટ ડીબી માટે pgsite તૈયાર કરો (Fix: આમાં પણ પાથ લાગવો જોઈએ)
    $current_suffix = $pgsite_paths[$current_db] ?? '';
    $final_current_pgsite = $base_domain . '/' . ltrim($current_suffix, '/');

    // ૨. કરંટ ડીબી અપડેટ
    $sets = []; $vals = [];
    foreach ($_POST as $key => $val) {
        if(!in_array($key, ['id', 'update_record', 'sync_dbs'])) { 
            $sets[] = "$key = ?"; 
            $vals[] = ($key == 'pgsite') ? $final_current_pgsite : $val; 
        }
    }
    $vals[] = $id;
    $pdo->prepare("UPDATE credentials SET " . implode(", ", $sets) . " WHERE id = ?")->execute($vals);

    // ૩. સિંક કરવા માટે પસંદ કરેલા ડેટાબેઝ
    $sync_targets = isset($_POST['sync_dbs']) ? $_POST['sync_dbs'] : [];

    foreach ($sync_targets as $db_name) {
        if($db_name == $current_db) continue; 
        try {
            $temp_pdo = getPDO($db_name, $db_host, $db_user, $db_pass, $db_port);
            $suffix = $pgsite_paths[$db_name] ?? '';
            $final_url = $base_domain . '/' . $suffix;

            $sql = "UPDATE credentials SET 
                    payu_key = ?, payu_salt = ?, 
                    razorpay_key_id = ?, razorpay_key_secret = ?, 
                    cashfree_app_id = ?, cashfree_secret_key = ?, 
                    pgsite = ? 
                    WHERE id IS NOT NULL";
            
            $temp_pdo->prepare($sql)->execute([
                $_POST['payu_key'], $_POST['payu_salt'],
                $_POST['razorpay_key_id'], $_POST['razorpay_key_secret'],
                $_POST['cashfree_app_id'], $_POST['cashfree_secret_key'],
                $final_url
            ]);
        } catch (Exception $e) { }
    }
    $msg = "<div class='alert alert-success mt-3 shadow-sm text-center'>સફળતા! ડેટા અને પાથ અપડેટ થઈ ગયા છે. ✅</div>";
}
?>
<!DOCTYPE html>
<html lang="gu">
<head>
    <meta charset="UTF-8">
    <title>Master Sync Panel - Flip</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #1a202c; color: #fff; }
        .sidebar { background: #2d3748; min-height: 100vh; padding: 25px 15px; }
        .sidebar a { color: #a0aec0; text-decoration: none; display: block; padding: 12px; border-radius: 8px; margin-bottom: 5px; font-weight: 500; font-size: 13px; }
        .sidebar a.active { background: #3182ce; color: white; }
        .main-content { background: #f7fafc; min-height: 100vh; padding: 30px; color: #2d3748; }
        .card-custom { border-radius: 12px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        label { font-weight: 700; font-size: 10px; color: #718096; text-transform: uppercase; margin-bottom: 4px; display: block; }
        .sync-label { background: #ed8936; color: white; padding: 1px 4px; border-radius: 3px; font-size: 9px; vertical-align: middle; }
        .db-select-box { background: #edf2f7; padding: 18px; border-radius: 10px; margin-bottom: 25px; border: 1px solid #cbd5e0; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar">
            <h6 class="text-muted small px-2 mb-4">DATABASES</h6>
            <?php foreach ($databases as $db): ?>
                <a href="?db=<?php echo $db; ?>" class="<?php echo ($current_db == $db) ? 'active' : ''; ?>">
                   📁 <?php echo strtoupper(str_replace('u543806883_', '', $db)); ?>
                </a>
            <?php endforeach; ?>
            <hr class="border-secondary mt-5">
            <a href="?logout=1" class="text-danger small">🚪 Logout</a>
        </div>

        <div class="col-md-10 main-content">
            <?php echo $msg; ?>

            <form method="POST">
                <!-- Sync Selector at TOP -->
                <div class="db-select-box text-dark shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 fw-bold">Select Databases to Sync:</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label text-primary fw-bold" for="selectAll" style="font-size: 12px; cursor:pointer;">SELECT ALL</label>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-4">
                        <?php foreach ($databases as $db): ?>
                            <div class="form-check">
                                <input class="form-check-input sync-check" type="checkbox" name="sync_dbs[]" value="<?php echo $db; ?>" id="chk_<?php echo $db; ?>">
                                <label class="form-check-label fw-bold small text-uppercase" for="chk_<?php echo $db; ?>" style="color:#2d3748; cursor:pointer;">
                                    <?php echo str_replace('u543806883_', '', $db); ?> <?php if($db == $current_db) echo "<span class='text-primary'>(Editing)</span>"; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php 
                $stmt = $pdo->query("SELECT * FROM credentials LIMIT 1");
                $data = $stmt->fetch();
                if ($data): 
                ?>
                <div class="card card-custom p-4 shadow-sm">
                    <h4 class="mb-4">EDITING: <span class="text-primary fw-bold text-uppercase"><?php echo str_replace('u543806883_', '', $current_db); ?></span></h4>
                    <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                    <div class="row">
                        <?php foreach ($data as $col => $val): 
                            if ($col == 'id') continue; 
                            $is_sync = in_array($col, ['payu_key','payu_salt','razorpay_key_id','razorpay_key_secret','cashfree_app_id','cashfree_secret_key','pgsite']);
                        ?>
                            <div class="col-md-4 mb-3">
                                <label><?php echo str_replace('_', ' ', $col); ?> <?php if($is_sync) echo "<span class='sync-label'>SYNC</span>"; ?></label>
                                <input type="text" name="<?php echo $col; ?>" class="form-control form-control-sm" value="<?php echo htmlspecialchars($val); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 pt-3 border-top text-end">
                        <button type="submit" name="update_record" class="btn btn-primary px-5 fw-bold shadow">SAVE & SYNC SELECTED</button>
                    </div>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
// Select All ફંક્શનલિટી
document.getElementById('selectAll').onclick = function() {
    var checkboxes = document.querySelectorAll('.sync-check');
    for (var checkbox of checkboxes) { checkbox.checked = this.checked; }
}
</script>

</body>
</html>

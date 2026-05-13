<?php
session_name("admin_session");
session_start();
require 'db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

// Redirect if admin not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

// Fetch admin info if not in session
$admin_id = $_SESSION['admin_id'] ?? 0;
if (!isset($_SESSION['admin_role'])) {
    $stmt = $pdo->prepare("SELECT role, username FROM admin_users WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['admin_role'] = $admin['role'] ?? 'Staff';
    $_SESSION['admin_username'] = $admin['username'] ?? 'Unknown';
}

$admin_role = $_SESSION['admin_role'];
$admin_username = $_SESSION['admin_username'];
$current_page = basename($_SERVER['PHP_SELF']);
$allowed_roles = ['admin'];

// Restrict access
if (!in_array($admin_role, $allowed_roles)) {
    $_SESSION['flash'] = [
        'message' => 'You do not have permission to access this page.',
        'type' => 'error'
    ];
    header('Location: products.php');
    exit();
}

// ----------------------------
// FUNCTIONS: Email + SMS
// ----------------------------
function sendWarrantyEmail($to, $fullname, $product, $code, $warranty) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aquinojenesis1@gmail.com';
        $mail->Password = 'xrpg gcde xgwk ccan'; // App password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('aquinojenesis1@gmail.com', 'Sentillas Air-Conditioning');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = 'Warranty Reminder – Unclaimed Warranty';
        $mail->Body = "
            Hello <b>$fullname</b>,<br><br>
            This is a friendly reminder that your purchased product is still under warranty.<br><br>
            <b>Product:</b> $product<br>
            <b>Warranty:</b> $warranty<br>
            <b>Warranty Code:</b> <span style='font-size:18px; color:#2563eb'><b>$code</b></span><br><br>
            Please keep this code safe. You will need it if you request warranty service.<br><br>
            If you already claimed your warranty, you may ignore this message.<br><br>
            — <b>Sentillas Air-Conditioning</b>
        ";
        $mail->send();
    } catch (Exception $e) {
        error_log("Warranty Email Error: {$mail->ErrorInfo}");
    }
}

function sendWarrantySMS($number, $fullname, $product, $code) {
    $api_key = "24b4949fd7a2ad804aa23f95a28e5acc";
    $sender = "SENTILLAS";
    $number = preg_replace('/[^0-9]/', '', $number);

    $message = "Hi $fullname! Reminder: Your warranty for $product is still active. Warranty Code: $code. Keep this code safe. - Sentillas";

    $data = [
        'apikey' => $api_key,
        'number' => $number,
        'message' => $message,
        'sendername' => $sender
    ];

    $ch = curl_init("https://api.semaphore.co/api/v4/messages");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

// ----------------------------
// AUTOMATIC WARRANTY REMINDER
// ----------------------------
$stmt = $pdo->prepare("
    SELECT 
        wc.id,
        wc.buyer_code,
        wc.last_reminded_at,
        wc.reminder_count,
        u.firstname,
        u.lastname,
        u.email,
        u.cellphone_number,
        wc.product_name,
        pd.warranty
    FROM warranty_claims wc
    JOIN users u ON wc.user_id = u.id
    LEFT JOIN products p ON wc.product_name COLLATE utf8mb4_general_ci = p.name COLLATE utf8mb4_general_ci
    LEFT JOIN product_descriptions pd ON pd.product_id = p.id
    WHERE wc.status != 'claimed'
      -- Only remind if at least 1 month has passed since purchase
      AND wc.created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)
      -- Only remind if never reminded or last reminder was 1 month ago
      AND (wc.last_reminded_at IS NULL OR wc.last_reminded_at < DATE_SUB(NOW(), INTERVAL 1 MONTH))
");


$stmt->execute();
$unclaimed = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($unclaimed as $c) {
    $fullname = $c['firstname'] . ' ' . $c['lastname'];

    sendWarrantyEmail($c['email'], $fullname, $c['product_name'], $c['buyer_code'], $c['warranty'] ?? 'N/A');
    sendWarrantySMS($c['cellphone_number'], $fullname, $c['product_name'], $c['buyer_code']);

    // Update reminder timestamp
    $pdo->prepare("
        UPDATE warranty_claims
        SET last_reminded_at = NOW(),
            reminder_count = reminder_count + 1
        WHERE id = ?
    ")->execute([$c['id']]);
}

// ----------------------------
// FETCH ALL CLAIMS FOR TABLE DISPLAY
// ----------------------------
$claims_stmt = $pdo->prepare("
    SELECT wc.*, u.firstname, u.lastname, pd.warranty
    FROM warranty_claims wc
    JOIN users u ON wc.user_id = u.id
    LEFT JOIN products p ON wc.product_name COLLATE utf8mb4_general_ci = p.name COLLATE utf8mb4_general_ci
    LEFT JOIN product_descriptions pd ON pd.product_id = p.id
    ORDER BY wc.created_at DESC
");

$claims_stmt->execute();
$claims = $claims_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Warranty Codes - Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
body { display: flex; height: 100vh; background: linear-gradient(135deg, #eef2ff, #f9fafb); color: #111827; overflow: hidden; }

/* Sidebar */
.sidebar { width: 250px; background: rgba(255, 255, 255, 0.25); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.3); box-shadow: 6px 0 25px rgba(0,0,0,0.05); color: #111827; padding: 30px 20px; display: flex; flex-direction: column; transition: 0.3s ease; }
.sidebar h2 { font-size: 1.5rem; margin-bottom: 40px; text-align: center; font-weight: 600; color: #111827; }
.admin-info { text-align: center; margin-bottom: 25px; padding: 16px; background: rgba(255,255,255,0.5); border-radius: 14px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); }
.admin-info p { margin: 5px 0; }
.sidebar hr { border: none; border-top: 1px solid rgba(0,0,0,0.1); margin: 20px 0; }
.sidebar a { text-decoration: none; color: #374151; padding: 12px 16px; margin: 6px 0; border-radius: 10px; display: flex; align-items: center; gap: 12px; font-size: 1rem; font-weight: 500; transition: all 0.25s ease; }
.sidebar a:hover { background: rgba(59,130,246,0.1); color: #2563eb; transform: translateX(4px); }
.sidebar a.active { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; box-shadow: 0 4px 12px rgba(37,99,235,0.3); }

/* Main */
.main { flex: 1; padding: 40px; overflow-y: auto; }
h1 { font-size: 1.8rem; margin-bottom: 24px; font-weight: 600; color: #111827; }

/* Search */
.search-bar input { width: 320px; padding: 10px 14px; font-size: 0.95rem; border-radius: 8px; border: 1px solid #d1d5db; margin-bottom: 20px; transition: 0.3s; }
.search-bar input:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 3px rgba(37,99,235,0.2); }

/* Table */
table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); overflow: hidden; }
th, td { padding: 14px 20px; border-bottom: 1px solid #f3f4f6; text-align: left; font-size: 0.95rem; }
th { background: #f9fafb; color: #111827; font-weight: 600; font-size: 0.9rem; }
tr:hover { background: #f3f4f6; }

/* Buttons & Badges */
.btn { padding: 6px 14px; border-radius: 6px; border: none; background: #2563eb; color: #fff; font-size: 0.85rem; cursor: pointer; transition: 0.3s; }
.btn:hover { background: #1d4ed8; }
.claimed {  font-weight: 600; font-size: 0.8rem; }
.not-claimed {  font-weight: 600; font-size: 0.8rem; }

/* Flash Message */
.flash-message { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); padding: 14px 20px; border-radius: 8px; font-weight: 500; min-width: 320px; text-align: center; font-size: 0.95rem; z-index: 9999; animation: fadeOut 4s forwards; }
.flash-message.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.flash-message.error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
@keyframes fadeOut { 0% { opacity: 1; } 80% { opacity: 1; } 100% { opacity: 0; display: none; } }
</style>
</head>
<body>
    
<div class="sidebar">
  <h2>Sentillas Airconditioning</h2>
  <div class="admin-info">
    <p><?= htmlspecialchars($admin_username) ?></p>
    <small>Role: <?= ucfirst(htmlspecialchars($admin_role)) ?></small>
  </div>
  <hr>
  
<a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a>
<a href="products.php" class="<?= $current_page == 'products.php' ? 'active' : '' ?>"><i class="fas fa-box"></i> Products</a>
<a href="orders.php" class="<?= $current_page == 'orders.php' ? 'active' : '' ?>"><i class="fas fa-shopping-cart"></i> Orders</a>
<a href="customers.php" class="<?= $current_page == 'customers.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> Customers</a>
<a href="admin_newsletter.php" class="<?= $current_page == 'admin_newsletter.php' ? 'active' : '' ?>"><i class="fas fa-envelope"></i> Newsletter</a>
<a href="maintenance_request.php" class="<?= $current_page == 'maintenance_request.php' ? 'active' : '' ?>"><i class="fas fa-tools"></i> Maintenance</a>
<?php if (in_array($admin_role, $allowed_roles)): ?>
<a href="warranty_codes.php" class="<?= $current_page=='warranty_codes.php'?'active':'' ?>"><i class="fas fa-shield-alt"></i> Warranty</a>
<?php endif; ?>
    <?php if ($admin_role === 'admin'): ?>
    <a href="add_admin.php" class="<?= $current_page=='add_admin.php'?'active':'' ?>"><i class="fas fa-user-cog"></i> Add User</a>
    <?php endif; ?>
</div>

<div class="main">
<h1><i class="fas fa-user-shield"></i> Warranty Codes</h1>

<?php if(isset($_SESSION['flash'])): $flash = $_SESSION['flash']; ?>
<div class="flash-message <?= $flash['type']; ?>"><?= $flash['message']; ?></div>
<?php unset($_SESSION['flash']); endif; ?>

<div class="search-bar">
    <input type="text" id="searchInput" placeholder="🔍 Search by name or claim code...">
</div>

<table id="warrantyTable">
<thead>
<tr>
    <th>#</th>
    <th>Customer</th>
    <th>Product</th>
    <th>Warranty</th>
    <th>Code</th>
    <th>Status</th>
    <th>Date Made</th>
    <th>Date Claimed</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php $i=1; foreach($claims as $claim): ?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($claim['firstname'].' '.$claim['lastname']) ?></td>
    <td><?= htmlspecialchars($claim['product_name']) ?></td>
    <td><?= htmlspecialchars($claim['warranty'] ?? '-') ?></td>
    <td><?= htmlspecialchars($claim['buyer_code']) ?></td>
    <td class="<?= $claim['status']=='claimed'?'claimed':'not-claimed' ?>">
        <?= ucfirst($claim['status']) ?>
    </td>
<td><?= $claim['created_at'] ? date('Y-m-d', strtotime($claim['created_at'])) : '-' ?></td>
<td><?= $claim['claim_date'] ? date('Y-m-d', strtotime($claim['claim_date'])) : '-' ?></td>

<td>
        <?php if($claim['status'] != 'claimed'): ?>
        <form method="post" action="claim_warranty.php" style="display:inline;">
            <input type="hidden" name="claim_id" value="<?= $claim['id'] ?>">
            <button type="submit" class="btn">Claim</button>
        </form>
        <?php else: ?>
            <span class="claimed">Already Claimed</span>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll('#warrantyTable tbody tr');
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
    });
});
</script>
</body>
</html>

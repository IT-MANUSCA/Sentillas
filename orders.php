<?php
session_name("admin_session");
session_start();
require 'db.php';

// Redirect if admin is not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

// Fetch admin role if not stored in session
if (!isset($_SESSION['admin_role'])) {
    $stmt = $pdo->prepare("SELECT username, role FROM admin_users WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['admin_role'] = $admin['role'] ?? 'Staff';
    $_SESSION['admin_username'] = $admin['username'] ?? 'Unknown';
}

$admin_role = $_SESSION['admin_role'];
$admin_username = $_SESSION['admin_username'];
$current_page = basename($_SERVER['PHP_SELF']);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

// Only allow Staff and above to view orders page
if (!in_array($admin_role, ['admin', 'staff'])) {
    $_SESSION['flash'] = [
        'message' => 'You do not have permission to access this page.',
        'type' => 'error'
    ];
    header('Location: dashboard.php');
    exit();
}

// Fetch orders including user info and warranty
$orders_stmt = $pdo->prepare("
    SELECT p.*, 
           u.firstname, u.lastname, u.cellphone_number, u.city, u.province, u.address, u.email,
           pd.warranty
    FROM payments p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN products prod ON p.product_name = prod.name
    LEFT JOIN product_descriptions pd ON prod.id = pd.product_id
    ORDER BY p.created_at DESC
");
$orders_stmt->execute();
$orders = $orders_stmt->fetchAll();

// Function to send email
function sendOrderEmail($to, $subject, $body){
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aquinojenesis1@gmail.com';
        $mail->Password = 'xrpg gcde xgwk ccan'; // app password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('aquinojenesis1@gmail.com', 'Sentillas Air-Conditioning');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

// ✅ SMS Function (Semaphore API)
function sendSMS($number, $message) {
    $api_key = "24b4949fd7a2ad804aa23f95a28e5acc"; // your API key
    $sender = "SENTILLAS";

    $number = preg_replace('/[^0-9]/', '', $number);

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

// ✅ Handle Confirm/Cancel actions with Email + SMS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['action'])) {
    if ($admin_role !== 'admin') {
        $_SESSION['flash'] = [
            'message' => 'You do not have permission to modify orders.',
            'type' => 'error'
        ];
        header("Location: orders.php");
        exit();
    }

    $order_id = intval($_POST['order_id']);
    $action = $_POST['action'];

    // Fetch user & product details
    $stmt_user = $pdo->prepare("
        SELECT p.email, p.product_name, p.quantity, p.user_id, u.firstname, u.lastname, u.cellphone_number
        FROM payments p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt_user->execute([$order_id]);
    $user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);

    $fullName = $user_info['firstname'] . " " . $user_info['lastname'];
    $phone = $user_info['cellphone_number'];
    $product = $user_info['product_name'];

if ($action === 'confirm') {

    // ✅ Update status and stock
    $pdo->prepare("UPDATE payments SET status = 'confirmed' WHERE id = ?")
        ->execute([$order_id]);

    $pdo->prepare("UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE name = ?")
        ->execute([$user_info['quantity'], $product]);

    // ✅ Generate Random Code (8 characters)
    $randomCode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

    // ✅ Save the code to payments table
    $pdo->prepare("UPDATE payments SET buyer_code = ? WHERE id = ?")
        ->execute([$randomCode, $order_id]);

    // ✅ Insert into warranty_claims table
    $pdo->prepare("
        INSERT INTO warranty_claims (order_id, user_id, fullname, product_name, buyer_code)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([
        $order_id,
        $user_info['user_id'],
        $fullName,
        $product,
        $randomCode
    ]);

    // === ESTIMATED DELIVERY ===
    $minDays = 3;
    $maxDays = 5;
    $estStart = date('F j, Y', strtotime("+$minDays days"));
    $estEnd = date('F j, Y', strtotime("+$maxDays days"));
    $timeSlot = "8:00 AM - 6:00 PM";

    // === EMAIL WITH ESTIMATED DELIVERY ===
    $subject = "Your Payment is Verified! Order Now Processing";
$body = "
    Hello $fullName,<br><br>

    Thank you for your payment. Your order is now <b>confirmed</b> and being processed.<br><br>

    <b>Your Warranty Code</b><br>
    <small>(Please save this code. You will need it for warranty claims.)</small><br>
    <span style='font-size:18px; color:#2563eb;'><b>$randomCode</b></span><br><br>

    <b>Estimated Delivery Window:</b><br>
    Between <b>$estStart</b> and <b>$estEnd</b><br>
    Time: <b>$timeSlot</b><br><br>

    <hr>

    <b>🔔 Important Reminders</b><br><br>

    🛡 <b>Warranty Reminder:</b><br>
    Please keep your warranty code safe. Warranty claims require this code and must be reported within the warranty period.<br><br>

    🧼 <b>Aircon Cleaning Reminder (After 6 Months):</b><br>
    To maintain performance, efficiency, and warranty validity, we highly recommend having your aircon professionally cleaned every <b>6 months</b>.<br><br>

    If you need maintenance or cleaning services, feel free to contact us anytime.<br><br>

    Thank you for choosing <b>Sentillas Air-Conditioning</b>.
";
    sendOrderEmail($user_info['email'], $subject, $body);

    // === SMS WITH ETA ===
    $sms = "Hi $fullName! Your order for $product is CONFIRMED. ETA: $estStart–$estEnd ($timeSlot). Warranty Code: $randomCode. Reminder: Keep your warranty code safe and have your aircon cleaned every 6 months. - Sentillas Air Conditioning";
    sendSMS($phone, $sms);

}
 elseif ($action === 'cancel') {

        // ✅ Update status
        $pdo->prepare("UPDATE payments SET status = 'cancelled' WHERE id = ?")
            ->execute([$order_id]);

        // ✅ Email Notification
        $subject = "Your Order Was Cancelled";
        $body = "
            Hello $fullName,<br><br>
            Unfortunately, your order has been cancelled.<br><br>
            - Sentillas Air-Conditioning
        ";
        sendOrderEmail($user_info['email'], $subject, $body);

        // ✅ SMS Notification
        $sms = "Hi $fullName, your order for $product has been cancelled. For help, message us. - Sentillas Air Conditioning";
        sendSMS($phone, $sms);
    }

    $_SESSION['flash'] = [
        'message' => "Order has been " . ($action === 'confirm' ? 'confirmed' : 'cancelled') . " and notifications sent!",
        'type' => 'success'
    ];
    header("Location: orders.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Orders - Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Inter', sans-serif;
}

body {
  display: flex;
  height: 100vh;
  background: linear-gradient(135deg, #eef2ff, #f9fafb);
  color: #111827;
  overflow: hidden;
}

/* Sidebar */
.sidebar {
  width: 250px;
  background: rgba(255, 255, 255, 0.25);
  backdrop-filter: blur(20px);
  border-right: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 6px 0 25px rgba(0,0,0,0.05);
  color: #111827;
  padding: 30px 20px;
  display: flex;
  flex-direction: column;
  transition: 0.3s ease;
}

.sidebar h2 {
  font-size: 1.5rem;
  margin-bottom: 40px;
  text-align: center;
  font-weight: 600;
  color: #111827;
}

.admin-info {
  text-align: center;
  margin-bottom: 25px;
  padding: 16px;
  background: rgba(255,255,255,0.5);
  border-radius: 14px;
  box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
}

.admin-info p {
  margin: 5px 0;
}

.sidebar hr {
  border: none;
  border-top: 1px solid rgba(0,0,0,0.1);
  margin: 20px 0;
}

.sidebar a {
  text-decoration: none;
  color: #374151;
  padding: 12px 16px;
  margin: 6px 0;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 1rem;
  font-weight: 500;
  transition: all 0.25s ease;
}

.sidebar a:hover {
  background: rgba(59,130,246,0.1);
  color: #2563eb;
  transform: translateX(4px);
}

.sidebar a.active {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #fff;
  box-shadow: 0 4px 12px rgba(37,99,235,0.3);
}

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

/* Buttons & Status */
.btn { padding: 6px 14px; border-radius: 6px; border: none; background: #2563eb; color: #fff; font-size: 0.85rem; cursor: pointer; transition: 0.3s; }
.btn:hover { background: #1d4ed8; }
.status-confirmed { background: #dcfce7; color: #166534; padding: 6px 10px; border-radius: 6px; font-weight: 600; font-size: 0.8rem; }
.status-cancelled { background: #fee2e2; color: #991b1b; padding: 6px 10px; border-radius: 6px; font-weight: 600; font-size: 0.8rem; }
.status-pending { background: #fef9c3; color: #92400e; padding: 6px 10px; border-radius: 6px; font-weight: 600; font-size: 0.8rem; }

/* Flash Message */
.flash-message { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); padding: 14px 20px; border-radius: 8px; font-weight: 500; min-width: 320px; text-align: center; font-size: 0.95rem; z-index: 9999; animation: fadeOut 4s forwards; }
.flash-message.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
@keyframes fadeOut { 0% { opacity: 1; } 80% { opacity: 1; } 100% { opacity: 0; display: none; } }

/* Modal */
.modal { display: none; position: fixed; z-index: 1000; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); }
.modal-content { background: #fff; margin: 5% auto; padding: 25px 30px; width: 520px; max-width: 90%; border-radius: 16px; box-shadow: 0 8px 25px rgba(0,0,0,0.25); font-size: 0.95rem; position: relative; }
.modal-content h2 { margin: 0 0 20px; font-size: 1.2rem; font-weight: 600; border-bottom: 1px solid #eee; padding-bottom: 10px; }
.close { position: absolute; right: 20px; top: 15px; font-size: 22px; font-weight: bold; color: #666; cursor: pointer; }
.close:hover { color: #000; }
.modal-content p { margin: 10px 0; display: flex; justify-content: space-between; }
.modal-content strong { color: #555; min-width: 140px; display: inline-block; }
#modalProof { margin-top: 10px; border-radius: 10px; border: 1px solid #ddd; padding: 5px; background: #fafafa; max-height: 250px; object-fit: cover; cursor:pointer; display:none; }
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
    <a href="warranty_codes.php" class="<?= $current_page=='warranty_codes.php'?'active':'' ?>"><i class="fas fa-shield-alt"></i> Warranty</a>
    
    <?php if ($admin_role === 'admin'): ?>
    <a href="add_admin.php" class="<?= $current_page=='add_admin.php'?'active':'' ?>"><i class="fas fa-user-cog"></i> Add User</a>
    <?php endif; ?>
  </div>

<div class="main">
    <h1><i class="fas fa-shopping-cart"></i> Orders</h1>

    <?php if(isset($_SESSION['flash'])): $flash = $_SESSION['flash']; ?>
        <div class="flash-message <?= $flash['type']; ?>"><?= $flash['message']; ?></div>
        <?php unset($_SESSION['flash']); endif; ?>

    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="🔍 Search orders...">
    </div>

    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Customer</th>
            <th>Product</th>
            <th>Qty</th>
            <th>Amount</th>
            <th>Date Paid</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody id="ordersTableBody">
        <?php $i = 1; foreach($orders as $order):
            $date_paid = date('Y-m-d', strtotime($order['created_at']));
            $status = $order['status'] ?? 'pending';
            $statusClass = $status === 'confirmed' ? 'status-confirmed' : ($status === 'cancelled' ? 'status-cancelled' : 'status-pending');
        ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($order['firstname'] . ' ' . $order['lastname']) ?></td>
                <td><?= htmlspecialchars($order['product_name']) ?></td>
                <td><?= htmlspecialchars($order['quantity']) ?></td>
                <td>₱<?= number_format($order['total_amount'],2) ?></td>
                <td><?= $date_paid ?></td>
                <td><span class="<?= $statusClass ?>"><?= ucfirst($status) ?></span></td>
                <td>
                    <form method="post" style="display:inline-block;">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <button type="submit" name="action" value="confirm" class="btn">Confirm</button>
                    </form>
                    <form method="post" style="display:inline-block;">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <button type="submit" name="action" value="cancel" class="btn">Cancel</button>
                    </form>
                    <button type="button" class="btn view-details" 
                        data-name="<?= htmlspecialchars($order['firstname'] . ' ' . $order['lastname']) ?>"
                        data-cellphone="<?= htmlspecialchars($order['cellphone_number']) ?>"
                        data-address="<?= htmlspecialchars($order['city'] . ', ' . $order['province'] . ', ' . $order['address']) ?>"
                        data-product="<?= htmlspecialchars($order['product_name']) ?>"
                        data-quantity="<?= htmlspecialchars($order['quantity']) ?>"
                        data-amount="₱<?= number_format($order['total_amount'],2) ?>"
                        data-email="<?= htmlspecialchars($order['email']) ?>"
                        data-payment="<?= htmlspecialchars($order['payment_method']) ?>"
                        data-date="<?= date('Y-m-d H:i', strtotime($order['payment_date'])) ?>"
                        data-status="<?= ucfirst($order['status']) ?>"
                        data-warranty="<?= htmlspecialchars($order['warranty'] ?? 'N/A') ?>"
                        data-proof="<?= $order['proof_of_payment'] ? htmlspecialchars($order['proof_of_payment']) : '' ?>">
                        View
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Order Details</h2>
        <p><strong>Customer Name:</strong> <span id="modalName"></span></p>
        <p><strong>Cellphone:</strong> <span id="modalCellphone"></span></p>
        <p><strong>Address:</strong> <span id="modalAddress"></span></p>
        <p><strong>Product:</strong> <span id="modalProduct"></span></p>
        <p><strong>Warranty:</strong> <span id="modalWarranty"></span></p>
        <p><strong>Quantity:</strong> <span id="modalQuantity"></span></p>
        <p><strong>Amount:</strong> <span id="modalAmount"></span></p>
        <p><strong>Email:</strong> <span id="modalEmail"></span></p>
        <p><strong>Payment Method:</strong> <span id="modalPayment"></span></p>
        <p><strong>Date Paid:</strong> <span id="modalDate"></span></p>
        <p><strong>Status:</strong> <span id="modalStatus"></span></p>
        <img id="modalProof" src="" alt="Proof of Payment" style="display:none; cursor:pointer;">
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    let query = this.value;
    fetch('search_orders.php?q=' + encodeURIComponent(query))
        .then(response => response.text())
        .then(data => {
            document.getElementById('ordersTableBody').innerHTML = data;
        })
        .catch(error => console.error('Error:', error));
});

// Modal functionality
const modal = document.getElementById("detailsModal");
const closeBtn = document.querySelector(".close");
const modalProof = document.getElementById("modalProof");

document.querySelectorAll(".view-details").forEach(btn => {
    btn.addEventListener("click", function() {
        document.getElementById("modalName").textContent = this.dataset.name;
        document.getElementById("modalCellphone").textContent = this.dataset.cellphone;
        document.getElementById("modalAddress").textContent = this.dataset.address;
        document.getElementById("modalProduct").textContent = this.dataset.product;
        document.getElementById("modalWarranty").textContent = this.dataset.warranty;
        document.getElementById("modalQuantity").textContent = this.dataset.quantity;
        document.getElementById("modalAmount").textContent = this.dataset.amount;
        document.getElementById("modalEmail").textContent = this.dataset.email;
        document.getElementById("modalPayment").textContent = this.dataset.payment;
        document.getElementById("modalDate").textContent = this.dataset.date;
        document.getElementById("modalStatus").textContent = this.dataset.status;

        if(this.dataset.proof){
            modalProof.src = this.dataset.proof;
            modalProof.style.display = "block";
        } else {
            modalProof.style.display = "none";
        }
        modal.style.display = "block";
    });
});

closeBtn.onclick = () => modal.style.display = "none";
window.onclick = e => { if(e.target == modal) modal.style.display = "none"; }

modalProof.addEventListener("click", function() {
    if(this.src){
        window.open(this.src, '_blank');
    }
});
</script>
</body>
</html>

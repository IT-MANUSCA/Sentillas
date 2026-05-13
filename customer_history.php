<?php
session_name("admin_session");
session_start();
require 'db.php';

// Redirect if admin is not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

// Detect current file for sidebar active state
$current_page = basename($_SERVER['PHP_SELF']);

// Get user ID from query string
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: customers.php');
    exit();
}

$user_id = intval($_GET['id']);

// Fetch user info
$stmt = $pdo->prepare("SELECT firstname, lastname, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['flash'] = [
        'message' => 'User not found!',
        'type' => 'error'
    ];
    header('Location: customers.php');
    exit();
}

// Fetch payments for this user
$payments_stmt = $pdo->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY payment_date DESC");
$payments_stmt->execute([$user_id]);
$payments = $payments_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?> - Payment History</title>
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

        /* Back Button */
        .back-btn { display: inline-block; margin-bottom: 20px; padding: 10px 16px; background: #2563eb; color: #fff; border-radius: 6px; text-decoration: none; transition: 0.3s; }
        .back-btn:hover { background: #1d4ed8; }

        /* Search */
        .search-bar input { width: 320px; padding: 10px 14px; font-size: 0.95rem; border-radius: 8px; border: 1px solid #d1d5db; margin-bottom: 20px; transition: 0.3s; }
        .search-bar input:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 3px rgba(37,99,235,0.2); }

        /* Table */
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); overflow: hidden; }
        th, td { padding: 14px 20px; border-bottom: 1px solid #f3f4f6; text-align: left; font-size: 0.95rem; }
        th { background: #f9fafb; color: #111827; font-weight: 600; font-size: 0.9rem; }
        tr:hover { background: #f3f4f6; }

        /* Buttons & Badges */
        .btn-view { padding: 6px 14px; border-radius: 6px; border: none; background: #2563eb; color: #fff; font-size: 0.85rem; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-block; }
        .btn-view:hover { background: #1d4ed8; }
        .badge { padding: 6px 10px; border-radius: 6px; font-weight: 600; font-size: 0.8rem; }
        .pending { background: #fee2e2; color: #991b1b; }
        .confirmed { background: #dcfce7; color: #166534; }
        .delivered { background: #cce5ff; color: #004085; }
        .no-proof { color: #888; font-style: italic; }

        /* Flash Message */
        .flash-message { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); padding: 14px 20px; border-radius: 8px; font-weight: 500; min-width: 320px; text-align: center; font-size: 0.95rem; z-index: 9999; animation: fadeOut 4s forwards; }
        .flash-message.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .flash-message.error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        @keyframes fadeOut { 0% { opacity: 1; } 80% { opacity: 1; } 100% { opacity: 0; display: none; } }
    </style>
</head>
<body>
    
    

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a>
    <a href="products.php" class="<?= $current_page == 'products.php' ? 'active' : '' ?>"><i class="fas fa-box"></i> Products</a>
    <a href="orders.php" class="<?= $current_page == 'orders.php' ? 'active' : '' ?>"><i class="fas fa-shopping-cart"></i> Orders</a>
    <a href="admin_newsletter.php" class="<?= $current_page == 'admin_newsletter.php' ? 'active' : '' ?>"><i class="fas fa-envelope"></i> Newsletter</a>
    <a href="maintenance_request.php" class="<?= $current_page == 'maintenance_request.php' ? 'active' : '' ?>"><i class="fas fa-tools"></i> Maintenance</a>
    <a href="warranty_codes.php" class="<?= $current_page=='warranty_codes.php'?'active':'' ?>"><i class="fas fa-user-shield"></i> Warranty</a>
    <a href="add_admin.php" class="<?= $current_page=='add_admin.php'?'active':'' ?>"><i class="fas fa-user-cog"></i> Add Admin</a> 
</div>

<div class="main">
    <a href="customers.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Customers</a>
    <h1>Payment History - <?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></h1>

    <?php if (isset($_SESSION['flash'])): $flash = $_SESSION['flash']; ?>
        <div class="flash-message <?= $flash['type']; ?>"><?= $flash['message']; ?></div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="🔍 Search by product, status, or method...">
    </div>

    <?php if (count($payments) > 0): ?>
    <table id="paymentsTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Total Amount</th>
                <th>Payment Method</th>
                <th>Proof</th>
                <th>Status</th>
                <th>Payment Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments as $index => $payment): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($payment['product_name']) ?></td>
                <td><?= htmlspecialchars($payment['quantity']) ?></td>
                <td>₱<?= number_format($payment['total_amount'], 2) ?></td>
                <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                <td>
                    <?php if (!empty($payment['proof_of_payment'])): ?>
                        <a href="<?= htmlspecialchars($payment['proof_of_payment']) ?>" target="_blank" class="btn-view"><i class="fas fa-eye"></i> View</a>
                    <?php else: ?>
                        <span class="no-proof">N/A</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge <?= strtolower($payment['status']) ?>">
                        <?= htmlspecialchars(ucfirst($payment['status'])) ?>
                    </span>
                </td>
                <td><?= date("M d, Y h:i A", strtotime($payment['payment_date'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No payments found for this user.</p>
    <?php endif; ?>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll('#paymentsTable tbody tr');
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(value) ? '' : 'none';
    });
});
</script>

</body>
</html>

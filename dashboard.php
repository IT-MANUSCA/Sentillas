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
    $stmt = $pdo->prepare("SELECT role FROM admin_users WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $_SESSION['admin_role'] = $stmt->fetchColumn();
}

$admin_role = $_SESSION['admin_role'];
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch admin info
$admin_id = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT username, role FROM admin_users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$admin_username = $admin['username'] ?? 'Unknown';
$admin_role = $admin['role'] ?? 'Staff';
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch Stats
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_maintenance = $pdo->query("SELECT COUNT(*) FROM maintenance_requests")->fetchColumn();
$total_subscribers = $pdo->query("SELECT COUNT(*) FROM newsletter_subscribers")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM payments WHERE status='pending'")->fetchColumn();
$confirmed_orders = $pdo->query("SELECT COUNT(*) FROM payments WHERE status='confirmed'")->fetchColumn();
$cancelled_orders = $pdo->query("SELECT COUNT(*) FROM payments WHERE status='cancelled'")->fetchColumn();

$average_rating = $pdo->query("SELECT ROUND(AVG(rating), 1) FROM website_ratings")->fetchColumn();
$total_ratings = $pdo->query("SELECT COUNT(*) FROM website_ratings")->fetchColumn();

// Rating card color logic
$rating_color = '#6b7280';
if ($average_rating >= 3.5) {
    $rating_color = '#16a34a'; // green
} elseif ($average_rating < 3.5 && $average_rating >= 3.0) {
    $rating_color = '#facc15'; // yellow
} elseif ($average_rating < 3.0) {
    $rating_color = '#dc2626'; // red
}

// Recent orders
$recent_orders = $pdo->query("
    SELECT payments.id, users.firstname, users.lastname, payments.total_amount, payments.status 
    FROM payments 
    JOIN users ON payments.user_id = users.id 
    ORDER BY payments.id DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Revenue chart (last 6 months)
$revenue_chart_data = [];
$months = [];
for ($i=5; $i>=0; $i--) {
    $month = date('M', strtotime("-$i month"));
    $month_num = date('m', strtotime("-$i month"));
    $months[] = $month;
    $stmt = $pdo->prepare("SELECT IFNULL(SUM(total_amount),0) FROM payments WHERE status='confirmed' AND MONTH(created_at)=?");
    $stmt->execute([$month_num]);
    $revenue_chart_data[] = (float)$stmt->fetchColumn();
}

// Top products
$top_products = $pdo->query("
    SELECT product_name, SUM(quantity) AS total_sold 
    FROM payments 
    WHERE status='confirmed' 
    GROUP BY product_name 
    ORDER BY total_sold DESC 
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

// Low Stock Products
$low_stock_threshold = 5;
$low_stock_products = $pdo->prepare("SELECT name, stock FROM products WHERE stock <= ?");
$low_stock_products->execute([$low_stock_threshold]);
$low_stock_list = $low_stock_products->fetchAll(PDO::FETCH_ASSOC);
$low_stock_count = count($low_stock_list);

$low_stock_tooltip = "";
foreach ($low_stock_list as $prod) {
    $low_stock_tooltip .= $prod['name'] . " (Stock: " . $prod['stock'] . ")\n";
}

// Customer Growth (last 6 months)
$customer_growth = [];
for ($i=5; $i>=0; $i--) {
    $month_num = date('m', strtotime("-$i month"));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE MONTH(created_at)=?");
    $stmt->execute([$month_num]);
    $customer_growth[] = (int)$stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
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

.admin-info p { margin: 5px 0; }

.sidebar hr { border: none; border-top: 1px solid rgba(0,0,0,0.1); margin: 20px 0; }

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

/* Main content */
.main { flex: 1; padding: 40px; overflow-y: auto; }

h1 {
  font-size: 1.9rem;
  font-weight: 600;
  margin-bottom: 28px;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Stats */
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
  margin-bottom: 40px;
}

.stat-card {
  background: rgba(255,255,255,0.6);
  backdrop-filter: blur(15px);
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 4px 25px rgba(0,0,0,0.05);
  display: flex;
  align-items: center;
  gap: 16px;
  transition: all 0.3s ease;
}

.stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }

.stat-card i { font-size: 1.6rem; color: #2563eb; }
.stat-card h3 { font-size: 1.4rem; font-weight: 600; color: #111827; }
.stat-card p { font-size: 0.9rem; color: #6b7280; }

/* Tables */
table {
  width: 100%;
  border-collapse: collapse;
  background: rgba(255,255,255,0.65);
  border-radius: 14px;
  overflow: hidden;
  backdrop-filter: blur(12px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.06);
  margin-top: 10px;
}
th, td { padding: 14px 18px; text-align: left; font-size: 0.95rem; }
th { background: rgba(255,255,255,0.85); font-weight: 600; color: #111827; }
tr:nth-child(even) { background: rgba(255,255,255,0.4); }
tr:hover { background: rgba(255,255,255,0.8); transition: 0.3s; }

/* Status colors */
.status-pending { color:#f59e0b; font-weight:600; }
.status-confirmed { color:#16a34a; font-weight:600; }
.status-cancelled { color:#dc2626; font-weight:600; }

/* Charts */
.charts-row { display: flex; flex-wrap: wrap; gap: 35px; margin-top: 20px; }
.chart-container {
  flex: 1;
  min-width: 280px;
  background: rgba(255,255,255,0.6);
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.05);
  backdrop-filter: blur(12px);
}
.chart-container h3 {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 10px;
  color: #1f2937;
}


.modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; backdrop-filter: blur(10px); background: rgba(0, 0, 0, 0.35); justify-content: center; align-items: center; }
.modal.active { display: flex; }
.modal-content { background: rgba(255,255,255,0.9); backdrop-filter: blur(20px); padding: 30px; border-radius: 16px; text-align: center; max-width: 400px; width: 90%; box-shadow: 0 6px 25px rgba(0,0,0,0.2); }
.modal-content button { padding: 10px 20px; border: none; border-radius: 10px; cursor: pointer; margin: 0 8px; font-weight: 500; transition: all 0.25s ease; }
#confirmLogout { background: #2563eb; color: #fff; }
#confirmLogout:hover { background: #1d4ed8; }
#cancelLogout { background: #f9fafb; border: 1px solid #1f2937; color: #1f2937; }
#cancelLogout:hover { background: #e5e7eb; }
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
  
    <a href="dashboard.php" class="<?= $current_page=='dashboard.php'?'active':'' ?>"><i class="fas fa-home"></i> Dashboard</a>
    <a href="products.php" class="<?= $current_page=='products.php'?'active':'' ?>"><i class="fas fa-box"></i> Products</a>
    <a href="orders.php" class="<?= $current_page=='orders.php'?'active':'' ?>"><i class="fas fa-shopping-cart"></i> Orders</a>
    <a href="customers.php" class="<?= $current_page=='customers.php'?'active':'' ?>"><i class="fas fa-users"></i> Customers</a>
    <a href="admin_newsletter.php" class="<?= $current_page=='admin_newsletter.php'?'active':'' ?>"><i class="fas fa-envelope"></i> Newsletter</a>
    <a href="maintenance_request.php" class="<?= $current_page=='maintenance_request.php'?'active':'' ?>"><i class="fas fa-tools"></i> Maintenance</a>
    <a href="warranty_codes.php" class="<?= $current_page=='warranty_codes.php'?'active':'' ?>"><i class="fas fa-shield-alt"></i> Warranty</a>  
    <?php if ($admin_role === 'admin'): ?>
    <a href="add_admin.php" class="<?= $current_page=='add_admin.php'?'active':'' ?>"><i class="fas fa-user-cog"></i> Add User</a>
    <?php endif; ?>
    <a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
<h1><i class="fas fa-home"></i> Dashboard Overview</h1>

<div class="stats">
    <div class="stat-card"><i class="fas fa-box"></i><div><h3><?= $total_products ?></h3><p>Products</p></div></div>
    <div class="stat-card"><i class="fas fa-shopping-cart"></i><div><h3><?= $total_orders ?></h3><p>Orders</p></div></div>
    <div class="stat-card"><i class="fas fa-users"></i><div><h3><?= $total_customers ?></h3><p>Customers</p></div></div>
    <div class="stat-card"><i class="fas fa-tools"></i><div><h3><?= $total_maintenance ?></h3><p>Maintenance</p></div></div>
    <div class="stat-card"><i class="fas fa-envelope"></i><div><h3><?= $total_subscribers ?></h3><p>Subscribers</p></div></div>
    <div class="stat-card"><i class="fas fa-exclamation-triangle"></i><div><h3><?= $pending_orders ?></h3><p>Pending Orders</p></div></div>

    <!-- Low Stock Card with Tooltip -->
    <div class="stat-card" id="lowStockCard" style="background:#dc2626;color:#fff;cursor:pointer;">
    <i class="fas fa-box-open"></i>
    <div>
        <h3><?= $low_stock_count ?></h3>
        <p>Low Stock</p>
    </div>
</div>


    <div class="stat-card" style="background:<?= $rating_color ?>;color:#fff;">
        <i class="fas fa-star" style="color:#fbbf24;"></i>
        <div><h3><?= $average_rating ?? 'N/A' ?>/5</h3><p>Avg. Website Rating (<?= $total_ratings ?>)</p></div>
    </div>
</div>

<?php if ($admin_role === 'admin'): ?>
<form method="post" action="generate_report.php" style="text-align:right; margin-bottom:15px;">
    <button type="submit" style="background:#111827;color:#fff;border:none;padding:10px 18px;border-radius:8px;cursor:pointer;font-size:0.9rem;">
        <i class="fas fa-download"></i> Download Report
    </button>
</form>
<?php endif; ?>

<h2>Recent Orders</h2>
<table>
<tr><th>ID</th><th>Customer</th><th>Total</th><th>Status</th></tr>
<?php foreach($recent_orders as $order): ?>
<tr>
    <td><?= $order['id'] ?></td>
    <td><?= htmlspecialchars($order['firstname'].' '.$order['lastname']) ?></td>
    <td><?= number_format($order['total_amount'],2) ?></td>
    <td class="status-<?= strtolower($order['status']) ?>"><?= ucfirst($order['status']) ?></td>
</tr>
<?php endforeach; ?>
</table>

<div class="charts-row">
<h2>Analytics</h2>
<hr>
<div class="chart-container">
    <h3>Revenue This Year</h3>
    <canvas id="revenueChart"></canvas>
</div>
<div class="chart-container">
    <h3>Customer Growth</h3>
    <canvas id="customerChart"></canvas>
</div>
</div>

<h2>Top Products</h2>
<table>
<tr><th>Product</th><th>Total Sold</th></tr>
<?php foreach($top_products as $prod): ?>
<tr>
    <td><?= htmlspecialchars($prod['product_name']) ?></td>
    <td><?= $prod['total_sold'] ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div class="modal" id="lowStockModal">
  <div class="modal-content">
    <h3 style="margin-bottom:10px;">Low Stock Products</h3>

    <?php if ($low_stock_count > 0): ?>
        <div style="text-align:left;max-height:250px;overflow-y:auto;">
            <?php foreach ($low_stock_list as $prod): ?>
                <p>
                    <strong><?= htmlspecialchars($prod['name']) ?></strong>
                    — Stock: <?= $prod['stock'] ?>
                </p>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No low stock items 🎉</p>
    <?php endif; ?>

    <div style="margin-top:20px;">
        <button id="closeLowStock" style="background:#2563eb;color:#fff;">Close</button>
    </div>
  </div>
</div>


<div class="modal" id="logoutModal">
<div class="modal-content">
<h3>Confirm Logout</h3>
<p>Are you sure you want to logout?</p>
<div style="margin-top:20px;">
    <button id="confirmLogout">Yes, Logout</button>
    <button id="cancelLogout">Cancel</button>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'Revenue (PHP)',
            data: <?= json_encode($revenue_chart_data) ?>,
            backgroundColor: 'rgba(37,99,235,0.7)',
            borderColor: 'rgba(37,99,235,1)',
            borderWidth: 1
        }]
    },
    options: { devicePixelRatio: 2, scales: { y: { beginAtZero: true } } }
});

// Customer Growth Chart
const customerCtx = document.getElementById('customerChart').getContext('2d');
new Chart(customerCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'New Customers',
            data: <?= json_encode($customer_growth) ?>,
            backgroundColor: 'rgba(16,185,129,0.4)',
            borderColor: 'rgba(5,150,105,1)',
            borderWidth: 2,
            tension: 0.3,
            fill: true
        }]
    },
    options: { devicePixelRatio: 2, scales: { y: { beginAtZero: true } } }
});

// Low Stock Modal
const lowStockCard = document.getElementById('lowStockCard');
const lowStockModal = document.getElementById('lowStockModal');
const closeLowStock = document.getElementById('closeLowStock');

if (lowStockCard) {
    lowStockCard.addEventListener('click', () => {
        lowStockModal.classList.add('active');
    });
}

closeLowStock.addEventListener('click', () => {
    lowStockModal.classList.remove('active');
});

// Close modal when clicking outside
lowStockModal.addEventListener('click', (e) => {
    if (e.target === lowStockModal) {
        lowStockModal.classList.remove('active');
    }
});

// Logout Modal
const logoutBtn = document.getElementById('logoutBtn');
const logoutModal = document.getElementById('logoutModal');
const confirmLogout = document.getElementById('confirmLogout');
const cancelLogout = document.getElementById('cancelLogout');

logoutBtn.addEventListener('click', (e) => {
    e.preventDefault();
    logoutModal.classList.add('active');
});
cancelLogout.addEventListener('click', () => { logoutModal.classList.remove('active'); });
confirmLogout.addEventListener('click', () => { window.location.href = 'admin_logout.php'; });


</script>
</body>
</html>

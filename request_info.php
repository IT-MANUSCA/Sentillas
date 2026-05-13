<?php
session_name("admin_session");
session_start();
require 'db.php';

// Admin auth
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid request.');
}

$request_id = (int)$_GET['id'];

// Admin info
$stmt = $pdo->prepare("SELECT username, role FROM admin_users WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_role = $admin['role'] ?? '';

// Fetch maintenance request
$stmt = $pdo->prepare("
    SELECT product_name, brand_name, warranty_code, image_path, video_path, issue, status, created_at
    FROM maintenance_requests
    WHERE id = ?
");
$stmt->execute([$request_id]);
$req = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$req) {
    die('Request not found.');
}

$current_page = 'maintenance_request.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Maintenance Request Info</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');

* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
body { display:flex; height:100vh; background:linear-gradient(135deg,#eef2ff,#f9fafb); color:#111827; }

/* Sidebar */
.sidebar {
  width:250px; padding:30px 20px;
  background:rgba(255,255,255,.25);
  backdrop-filter:blur(20px);
  border-right:1px solid rgba(255,255,255,.3);
}
.sidebar h2 { text-align:center; margin-bottom:30px; }
.sidebar a {
  display:flex; align-items:center; gap:12px;
  padding:12px 16px; border-radius:10px;
  text-decoration:none; color:#374151; margin:6px 0;
}
.sidebar a.active, .sidebar a:hover {
  background:linear-gradient(135deg,#2563eb,#1d4ed8);
  color:#fff;
}

/* Main */
.main {
  flex: 1;
  padding: 40px;
  max-width: 900px;
  margin: 0 auto;
}
h1 { font-size:1.8rem; margin-bottom:25px; display:flex; gap:10px; }

/* Info Card */
.info-card {
  display: flex;
  flex-wrap: wrap;
  gap: 30px;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 6px 20px rgba(0,0,0,.08);
  padding: 30px;
  align-items: flex-start;
}

.info-details {
  flex:1;
  min-width: 250px;
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.info-item span {
  display:block;
  font-size:.85rem;
  color:#6b7280;
  margin-bottom:3px;
}
.info-item strong {
  font-size:1rem;
  color:#111827;
  word-break: break-word;
}

.info-media {
  flex:1;
  min-width: 250px;
  display:flex;
  flex-direction:column;
  gap:20px;
}

.info-media img, .info-media video {
  width:100%;
  border-radius:12px;
  box-shadow:0 4px 14px rgba(0,0,0,.12);
}

.back-btn {
  display:inline-block;
  margin-top:25px;
  padding:10px 16px;
  background:#2563eb;
  color:#fff;
  border-radius:8px;
  text-decoration:none;
}
.back-btn:hover { background:#1d4ed8; }
</style>
</head>

<body>

<div class="sidebar">
  <h2>Sentillas Airconditioning</h2>
  
  <a href="dashboard.php" class="<?= $current_page=='dashboard.php'?'active':'' ?>"><i class="fas fa-home"></i> Dashboard</a>
  <a href="products.php" class="<?= $current_page=='products.php'?'active':'' ?>"><i class="fas fa-box"></i> Products</a>
  <a href="orders.php" class="<?= $current_page=='orders.php'?'active':'' ?>"><i class="fas fa-shopping-cart"></i> Orders</a>
  <a href="customers.php" class="<?= $current_page=='customers.php'?'active':'' ?>"><i class="fas fa-users"></i> Customers</a>
  <a href="admin_newsletter.php" class="<?= $current_page=='admin_newsletter.php'?'active':'' ?>"><i class="fas fa-envelope"></i> Newsletter</a>
  <a href="warranty_codes.php" class="<?= $current_page=='warranty_codes.php'?'active':'' ?>"><i class="fas fa-shield-alt"></i> Warranty</a>
  <?php if ($admin_role === 'admin'): ?>
  <a href="add_admin.php" class="<?= $current_page=='add_admin.php'?'active':'' ?>"><i class="fas fa-user-cog"></i> Add User</a>
  <?php endif; ?>
</div>

<div class="main">
  <h1><i class="fas fa-info-circle"></i> Maintenance Request Info</h1>

  <div class="info-card">
    <div class="info-details">
      <div class="info-item">
        <span>Product Name</span>
        <strong><?= htmlspecialchars($req['product_name'] ?? 'N/A') ?></strong>
      </div>
      <div class="info-item">
        <span>Brand Name</span>
        <strong><?= htmlspecialchars($req['brand_name'] ?? 'N/A') ?></strong>
      </div>
      <div class="info-item">
        <span>Warranty Code</span>
        <strong><?= $req['warranty_code'] ?: 'Not Provided' ?></strong>
      </div>

      <div class="info-item">
        <span>Issue Reported</span>
        <strong><?= htmlspecialchars($req['issue'] ?? 'No details provided') ?></strong>
      </div>
      <div class="info-item">
        <span>Requested On</span>
        <strong><?= date('M d, Y H:i', strtotime($req['created_at'])) ?></strong>
      </div>
    </div>

    <div class="info-media">
      <?php if (!empty($req['image_path'])): ?>
        <img src="<?= htmlspecialchars($req['image_path']) ?>" alt="Request Image"/>
      <?php endif; ?>
      <?php if (!empty($req['video_path'])): ?>
        <video controls>
          <source src="<?= htmlspecialchars($req['video_path']) ?>" type="video/mp4">
        </video>
      <?php endif; ?>
    </div>
  </div>

  <a href="maintenance_request.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
</div>

</body>
</html>

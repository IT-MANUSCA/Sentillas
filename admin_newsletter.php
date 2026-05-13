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

// 🔥 NEW: Only "admin" allowed — superadmin removed
if ($admin_role !== 'admin') {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Access denied. You do not have permission to access this page.'
    ];
    header('Location: products.php');
    exit();
}

// Fetch admin info
$admin_id = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT username, role FROM admin_users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$admin_username = $admin['username'] ?? 'Unknown';
$admin_role = $admin['role'] ?? 'Admin';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Newsletter - Admin Panel</title>
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
    .main { flex:1; padding:40px; overflow-y:auto; }
    h1 { font-size:1.8rem; margin-bottom:24px; font-weight:600; color:#111827; }

    /* Form */
    form { background:#fff; padding:30px; border-radius:12px; box-shadow:0 4px 16px rgba(0,0,0,0.06); max-width:800px; margin:auto; }
    form label { font-size:1rem; font-weight:600; margin:12px 0 6px; display:block; color:#111827; }
    form input[type="text"], form textarea {
      width:100%; padding:12px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:0.95rem; margin-bottom:16px;
    }
    form input:focus, form textarea:focus {
      border-color:#2563eb; outline:none; box-shadow:0 0 0 3px rgba(37,99,235,0.2);
    }
    form textarea { resize:vertical; min-height:180px; }
    form button {
      background:#2563eb; color:#fff; padding:12px 20px; border:none; border-radius:8px;
      font-size:0.95rem; cursor:pointer; transition:0.3s; display:flex; align-items:center; gap:8px;
    }
    form button:hover { background:#1d4ed8; }

    /* Flash Message */
    .flash-message {
      position:fixed; top:20px; left:50%; transform:translateX(-50%);
      padding:14px 20px; border-radius:8px; font-weight:500; min-width:320px;
      text-align:center; font-size:0.95rem; z-index:9999; animation:fadeOut 4s forwards;
    }
    .flash-message.success { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
    .flash-message.error { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }

    @keyframes fadeOut { 0%{opacity:1;} 80%{opacity:1;} 100%{opacity:0; display:none;} }
  </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <h2>Sentillas Airconditioning</h2>
  <div class="admin-info">
    <p><?= htmlspecialchars($admin_username) ?></p>
    <small>Role: Admin</small>
  </div>
  <hr>

  <a href="dashboard.php" class="<?= $current_page=='dashboard.php'?'active':'' ?>"><i class="fas fa-home"></i> Dashboard</a>
  <a href="products.php" class="<?= $current_page=='products.php'?'active':'' ?>"><i class="fas fa-box"></i> Products</a>
  <a href="orders.php" class="<?= $current_page=='orders.php'?'active':'' ?>"><i class="fas fa-shopping-cart"></i> Orders</a>
  <a href="customers.php" class="<?= $current_page=='customers.php'?'active':'' ?>"><i class="fas fa-users"></i> Customers</a>
  <a href="admin_newsletter.php" class="<?= $current_page=='admin_newsletter.php'?'active':'' ?>"><i class="fas fa-envelope"></i> Newsletter</a>
  <a href="maintenance_request.php" class="<?= $current_page=='maintenance_request.php'?'active':'' ?>"><i class="fas fa-tools"></i> Maintenance</a>
  <a href="warranty_codes.php" class="<?= $current_page=='warranty_codes.php'?'active':'' ?>"><i class="fas fa-shield-alt"></i> Warranty</a>

  <!-- 🔥 Add Admin — now allowed for ADMIN (since superadmin removed) -->
  <a href="add_admin.php" class="<?= $current_page=='add_admin.php'?'active':'' ?>">
      <i class="fas fa-user-cog"></i> Add User
  </a>

</div>

<!-- Main -->
<div class="main">
  <h1><i class="fas fa-envelope"></i> Send Newsletter</h1>

  <!-- Flash Message -->
  <?php if (isset($_SESSION['flash'])): $flash = $_SESSION['flash']; ?>
    <div class="flash-message <?= $flash['type']; ?>"><?= $flash['message']; ?></div>
    <?php unset($_SESSION['flash']); ?>
  <?php endif; ?>

  <!-- Newsletter Form -->
  <form action="newsletter_submit.php" method="POST">
    <label for="title">Newsletter Title</label>
    <input type="text" name="title" id="title" placeholder="Enter title..." required>

    <label for="content">Newsletter Content</label>
    <textarea name="content" id="content" placeholder="Write your message here..." required></textarea>

    <button type="submit" name="send_newsletter"><i class="fas fa-paper-plane"></i> Send Newsletter</button>
  </form>
</div>

</body>
</html>

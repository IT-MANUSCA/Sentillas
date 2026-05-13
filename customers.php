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
$admin_id = $_SESSION['admin_id'];

// Fetch admin info
$stmt = $pdo->prepare("SELECT username, role FROM admin_users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$admin_username = $admin['username'] ?? 'Unknown';
$admin_role = $admin['role'] ?? 'admin';
$current_page = basename($_SERVER['PHP_SELF']);

// Automatically delete unverified users older than 30 seconds
$delete_stmt = $pdo->prepare("DELETE FROM users WHERE is_verified = 0 AND created_at <= (NOW() - INTERVAL 30 SECOND)");
$delete_stmt->execute();

// Handle delete user manually (only Super Admin can delete)
if (isset($_GET['delete'])) {
    if ($admin_role === 'admin') {
        $id = intval($_GET['delete']);
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $_SESSION['flash'] = [
            'message' => 'Customer deleted successfully!',
            'type' => 'error'
        ];
    } else {
        $_SESSION['flash'] = [
            'message' => 'You do not have permission to delete users.',
            'type' => 'error'
        ];
    }
    header("Location: customers.php");
    exit();
}

// Fetch users initially
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customers - Admin Panel</title>
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
    h1 { font-size: 1.8rem; margin-bottom: 24px; font-weight: 600; color: #111827; display: flex; align-items: center; gap: 10px; }

    /* Search */
    .search-bar input { width: 320px; padding: 10px 14px; font-size: 0.95rem; border-radius: 8px; border: 1px solid #d1d5db; margin-bottom: 20px; transition: 0.3s; }
    .search-bar input:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 3px rgba(37,99,235,0.2); }

    /* Table */
    table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); overflow: hidden; }
    th, td { padding: 14px 20px; border-bottom: 1px solid #f3f4f6; text-align: left; font-size: 0.95rem; }
    th { background: #f9fafb; color: #111827; font-weight: 600; font-size: 0.9rem; }
    tr:hover { background: #f3f4f6; }

    /* Buttons & Status */
    .btn { padding: 6px 14px; border-radius: 6px; border: none; background: #2563eb; color: #fff; font-size: 0.85rem; cursor: pointer; transition: 0.3s; text-decoration: none; }
    .btn:hover { background: #1d4ed8; }
    .btn-delete { background: #f3f4f6; color: #111; }
    .btn-delete:hover { background: #e5e7eb; }

    .status-confirmed { background: #dcfce7; color: #166534; padding: 6px 10px; border-radius: 6px; font-weight: 600; font-size: 0.8rem; }
    .status-cancelled { background: #fee2e2; color: #991b1b; padding: 6px 10px; border-radius: 6px; font-weight: 600; font-size: 0.8rem; }
    .status-pending { background: #fef9c3; color: #92400e; padding: 6px 10px; border-radius: 6px; font-weight: 600; font-size: 0.8rem; }

    /* Flash Message */
    .flash-message { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); padding: 14px 20px; border-radius: 8px; font-weight: 500; min-width: 320px; text-align: center; font-size: 0.95rem; z-index: 9999; animation: fadeOut 4s forwards; }
    .flash-message.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .flash-message.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    @keyframes fadeOut { 0% { opacity: 1; } 80% { opacity: 1; } 100% { opacity: 0; display: none; } }

    /* Modal */
    .modal { display: none; position: fixed; z-index: 1000; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); justify-content: center; align-items: center; }
    .modal.active { display: flex; }
    .modal-content { background: #fff; margin: 5% auto; padding: 25px 30px; width: 520px; max-width: 90%; border-radius: 16px; box-shadow: 0 8px 25px rgba(0,0,0,0.25); font-size: 0.95rem; position: relative; text-align: center; }
    .modal-content h3 { margin-bottom: 16px; font-size: 1.3rem; color: #111827; }
    .modal-content p { margin-bottom: 20px; font-size: 0.95rem; }
    .modal-buttons { display: flex; justify-content: center; gap: 12px; }
    .btn-confirm, .btn-cancel { padding: 10px 18px; border: none; border-radius: 8px; cursor: pointer; font-size: 0.9rem; font-weight: 500; }
    .btn-confirm { background: #dc2626; color: #fff; }
    .btn-confirm:hover { background: #b91c1c; }
    .btn-cancel { background: #6b7280; color: #fff; }
    .btn-cancel:hover { background: #4b5563; }
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
  </div>

  <!-- Main -->
  <div class="main">
    <h1><i class="fas fa-users"></i> Registered Customers</h1>

    <!-- Flash Message -->
    <?php if (isset($_SESSION['flash'])): $flash = $_SESSION['flash']; ?>
      <div class="flash-message <?= $flash['type']; ?>"><?= $flash['message']; ?></div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Search Bar -->
    <div class="search-bar">
      <input type="text" id="searchInput" placeholder="🔍 Search customers...">
    </div>

    <!-- Customers Table -->
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Username</th>
          <th>Status</th>
          <th>Registered At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="customerTableBody">
        <?php $i=1; while ($user=$users->fetch()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($user['firstname'].' '.$user['lastname']) ?></td>
          <td><?= htmlspecialchars($user['email']) ?></td>
          <td><?= htmlspecialchars($user['username']) ?></td>
          <td>
            <span class="<?= $user['is_verified'] ? 'status-confirmed' : 'status-cancelled' ?>">
              <?= $user['is_verified'] ? 'Verified' : 'Not Verified' ?>
            </span>
          </td>
          <td><?= date("M d, Y h:i A", strtotime($user['created_at'])) ?></td>
<td>
    <a href="customer_history.php?id=<?= $user['id'] ?>" class="btn"><i class="fas fa-clock"></i> History</a>
    <?php if ($admin_role === 'admin'): ?>
        <a href="customers.php?delete=<?= $user['id'] ?>" class="btn btn-delete"><i class="fas fa-trash"></i> Delete</a>
    <?php endif; ?>
</td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal" id="deleteModal">
    <div class="modal-content">
      <h3>Confirm Deletion</h3>
      <p>Are you sure you want to delete this customer?</p>
      <div class="modal-buttons">
        <button id="confirmDelete" class="btn-confirm">Yes, Delete</button>
        <button id="cancelDelete" class="btn-cancel">Cancel</button>
      </div>
    </div>
  </div>

  <script>
    // Live search
    document.getElementById('searchInput').addEventListener('keyup', function() {
      let query = this.value;
      fetch('search_customers.php?q=' + encodeURIComponent(query))
        .then(response => response.text())
        .then(data => {
          document.getElementById('customerTableBody').innerHTML = data;
        })
        .catch(error => console.error('Error:', error));
    });

    // Delete confirmation modal
    let deleteButtons = document.querySelectorAll('.btn-delete');
    let modal = document.getElementById('deleteModal');
    let confirmBtn = document.getElementById('confirmDelete');
    let cancelBtn = document.getElementById('cancelDelete');
    let deleteUrl = '';

    deleteButtons.forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        deleteUrl = this.href;
        modal.classList.add('active');
      });
    });

    confirmBtn.addEventListener('click', function() {
      window.location.href = deleteUrl;
    });

    cancelBtn.addEventListener('click', function() {
      modal.classList.remove('active');
    });
  </script>
</body>
</html>

<?php
session_name("admin_session");
session_start();
require 'db.php';

// Redirect if admin is not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

// Fetch admin info
$admin_id = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT username, role FROM admin_users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$admin_username = $admin['username'] ?? 'Unknown';
$admin_role = strtolower($admin['role'] ?? 'staff');
$current_page = basename($_SERVER['PHP_SELF']);

// ❌ Staff cannot add, edit, or delete
if ($admin_role === 'staff' && ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['delete']))) {
    $_SESSION['flash'] = ['message' => 'Access Denied: Staff cannot modify products.', 'type' => 'error'];
    header("Location: products.php");
    exit();
}

// Handle Add Product (Admin Only)
if (isset($_POST['add_product'])) {
    if ($admin_role !== 'admin') {
        $_SESSION['flash'] = ['message' => 'Access Denied: Only Admin can add products.', 'type' => 'error'];
        header("Location: products.php");
        exit();
    }

    $name  = trim($_POST['name']);
    $brand = trim($_POST['brand']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);

    $image = "uploads/default.png";
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName   = time() . "_" . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image = $targetFile;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO products (name, brand, price, stock, image) VALUES (:name, :brand, :price, :stock, :image)");
    $stmt->execute([':name' => $name, ':brand' => $brand, ':price' => $price, ':stock' => $stock, ':image' => $image]);

    $_SESSION['flash'] = ['message' => '✅ Product added successfully!', 'type' => 'success'];
    header("Location: products.php");
    exit();
}

// Handle Delete Product (Admin Only)
if (isset($_GET['delete']) && $admin_role === 'admin') {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);

    $_SESSION['flash'] = ['message' => '🗑️ Product deleted successfully!', 'type' => 'error'];
    header("Location: products.php");
    exit();
}

// Fetch Products
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC");
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Products - Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');

*{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif;}
body{display:flex;height:100vh;background:linear-gradient(135deg,#eef2ff,#f9fafb);color:#111827;overflow:hidden;}

/* Sidebar */
.sidebar{
  width:250px;background:rgba(255,255,255,0.25);backdrop-filter:blur(20px);
  border-right:1px solid rgba(255,255,255,0.3);box-shadow:6px 0 25px rgba(0,0,0,0.05);
  padding:30px 20px;display:flex;flex-direction:column;transition:0.3s ease;
}
.sidebar h2{font-size:1.5rem;margin-bottom:40px;text-align:center;font-weight:600;color:#111827;}
.admin-info{text-align:center;margin-bottom:25px;padding:16px;background:rgba(255,255,255,0.5);
  border-radius:14px;box-shadow:inset 0 1px 3px rgba(0,0,0,0.1);}
.admin-info p{margin:5px 0;}
.sidebar hr{border:none;border-top:1px solid rgba(0,0,0,0.1);margin:20px 0;}
.sidebar a{
  text-decoration:none;color:#374151;padding:12px 16px;margin:6px 0;border-radius:10px;
  display:flex;align-items:center;gap:12px;font-size:1rem;font-weight:500;transition:all 0.25s ease;
}
.sidebar a:hover{background:rgba(59,130,246,0.1);color:#2563eb;transform:translateX(4px);}
.sidebar a.active{
  background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;
  box-shadow:0 4px 12px rgba(37,99,235,0.3);
}

/* Main */
.main{flex:1;padding:40px;overflow-y:auto;}
h1{font-size:1.8rem;margin-bottom:24px;font-weight:600;color:#111827;display:flex;align-items:center;gap:10px;}

/* Flash Message */
.flash-message{
  position:fixed;top:20px;left:50%;transform:translateX(-50%);
  padding:14px 20px;border-radius:8px;font-weight:500;min-width:320px;text-align:center;
  font-size:0.95rem;z-index:9999;animation:fadeOut 4s forwards;
}
.flash-message.success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;}
.flash-message.error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;}
@keyframes fadeOut{0%{opacity:1;}80%{opacity:1;}100%{opacity:0;display:none;}}

/* Form */
form{
  background:#fff;padding:30px;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,0.06);max-width:800px;margin:auto;margin-bottom:30px;
}
form label{font-size:1rem;font-weight:600;margin:12px 0 6px;display:block;color:#111827;}
form input[type="text"],form input[type="number"],form input[type="file"]{
  width:100%;padding:12px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:0.95rem;margin-bottom:16px;
}
form input:focus{border-color:#2563eb;outline:none;box-shadow:0 0 0 3px rgba(37,99,235,0.2);}
form button{
  background:#2563eb;color:#fff;padding:12px 20px;border:none;border-radius:8px;font-size:0.95rem;cursor:pointer;display:flex;align-items:center;gap:8px;transition:0.3s;
}
form button:hover{background:#1d4ed8;}

/* Product Cards */
.products{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:20px;}
.product{
  background:#fff;border-radius:12px;padding:20px;text-align:center;box-shadow:0 4px 16px rgba(0,0,0,0.06);transition:0.3s;
}
.product:hover{transform:translateY(-5px);box-shadow:0 8px 20px rgba(0,0,0,0.1);}
.product img{width:100%;height:180px;object-fit:cover;border-radius:12px;margin-bottom:10px;}
.product h3{margin:5px 0;font-size:1.1rem;color:#111827;}
.product p{margin:3px 0;font-size:0.9rem;color:#374151;}
.stock{font-weight:600;color:#166534;}

/* Buttons */
.actions{margin-top:10px;display:flex;justify-content:center;gap:10px;flex-wrap:wrap;}
.actions a{
  padding:8px 14px;border-radius:8px;font-size:0.9rem;text-decoration:none;transition:all 0.2s ease;font-weight:500;
}
.btn-edit{background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;}
.btn-edit:hover{opacity:0.9;transform:translateY(-2px);}
.btn-delete{background:rgba(255,255,255,0.5);color:#111;border:1px solid #e5e7eb;}
.btn-delete:hover{background:#f3f4f6;}
</style>
</head>
<body>

<!-- Sidebar -->
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
  <h1><i class="fas fa-box"></i> Manage Products</h1>

  <?php if(isset($_SESSION['flash'])): ?>
    <div class="flash-message <?= $_SESSION['flash']['type']; ?>">
      <?= $_SESSION['flash']['message']; ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
  <?php endif; ?>

  <?php if(in_array($admin_role,['superadmin','super admin','admin'])): ?>
  <form method="POST" enctype="multipart/form-data">
    <label>Product Name</label>
    <input type="text" name="name" placeholder="Product Name" required>
    <label>Brand</label>
    <input type="text" name="brand" placeholder="Brand" required>
    <label>Price</label>
    <input type="number" step="0.01" name="price" placeholder="Price" required>
    <label>Stock Quantity</label>
    <input type="number" name="stock" placeholder="Stock Quantity" required>
    <label>Image</label>
    <input type="file" name="image" accept="image/*">
    <button type="submit" name="add_product"><i class="fas fa-plus"></i> Add Product</button>
  </form>
  <?php endif; ?>

  <div class="products">
    <?php while($row=$products->fetch()): ?>
      <div class="product">
        <img src="<?= htmlspecialchars($row['image']) ?>" alt="Product Image">
        <h3><?= htmlspecialchars($row['name']) ?></h3>
        <p><strong>Brand:</strong> <?= htmlspecialchars($row['brand']) ?></p>
        <p><strong>Price:</strong> ₱<?= number_format($row['price'],2) ?></p>
        <p class="stock">In Stock: <?= $row['stock'] ?></p>
        <div class="actions">
          <?php if(in_array($admin_role,['superadmin','super admin','admin'])): ?>
            <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
            <a href="add_details.php?product_id=<?= $row['id'] ?>" class="btn-edit"><i class="fas fa-info-circle"></i> Add Details</a>
          <?php endif; ?>
          <?php if(in_array($admin_role,['superadmin','super admin'])): ?>
            <a href="products.php?delete=<?= $row['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this product?');"><i class="fas fa-trash"></i> Delete</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>

</body>
</html>

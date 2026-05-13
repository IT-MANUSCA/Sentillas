<?php
session_start();
include 'db.php'; // This should define $pdo

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch product details securely using PDO
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    echo "Product not found!";
    exit();
}

// Handle update
if (isset($_POST['update_product'])) {
    $name = $_POST['name'];
    $brand = $_POST['brand'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $image = $product['image']; // Default to old image

    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir);
        $fileName = time() . "_" . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image = $targetFile;
        }
    }

    $stmt = $pdo->prepare("UPDATE products 
                           SET name = :name, brand = :brand, price = :price, stock = :stock, image = :image 
                           WHERE id = :id");
    $stmt->execute([
        ':name' => $name,
        ':brand' => $brand,
        ':price' => $price,
        ':stock' => $stock,
        ':image' => $image,
        ':id' => $id
    ]);

    $_SESSION['flash'] = [
        'message' => 'Product updated successfully!',
        'type' => 'success'
    ];

    header("Location: products.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Product</title>
  <style>
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background: #fafafa;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
    }
    .card {
      background: #fff;
      width: 400px;
      padding: 24px;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    h2 {
      font-size: 1.4rem;
      margin-bottom: 20px;
      font-weight: 600;
      color: #222;
      text-align: center;
    }
    .form-group {
      margin-bottom: 15px;
    }
    label {
      display: block;
      margin-bottom: 6px;
      font-size: 0.85rem;
      color: #555;
    }
    input {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 0.95rem;
      background: #fdfdfd;
    }
    input:focus {
      outline: none;
      border-color: #333;
    }
    .preview {
      margin: 15px 0;
      text-align: center;
    }
    .preview img {
      max-width: 180px;
      border-radius: 8px;
      border: 1px solid #eee;
    }
    .actions {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      margin-top: 20px;
    }
    .btn {
      flex: 1;
      padding: 10px;
      border: none;
      border-radius: 8px;
      font-size: 0.95rem;
      cursor: pointer;
      transition: 0.2s;
    }
    .btn-save {
      background: #111;
      color: #fff;
    }
    .btn-save:hover {
      background: #333;
    }
    .btn-cancel {
      background: #eee;
      color: #111;
    }
    .btn-cancel:hover {
      background: #ddd;
    }
    a.btn-cancel {
      text-align: center;
      display: inline-block;
      line-height: 2.3em;
      text-decoration: none;
    }

    .flash-message {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      padding: 12px 20px;
      border-radius: 8px;
      font-weight: 500;
      min-width: 300px;
      text-align: center;
      z-index: 9999;
      animation: fadeOut 4s forwards;
    }
    .flash-message.success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    .flash-message.error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    @keyframes fadeOut {
      0% {opacity:1;}
      80% {opacity:1;}
      100% {opacity:0; display:none;}
    }
  </style>
</head>
<body>

  <?php if(isset($_SESSION['flash'])): 
      $flash = $_SESSION['flash'];
  ?>
    <div class="flash-message <?= $flash['type']; ?>">
        <?= $flash['message']; ?>
    </div>
  <?php unset($_SESSION['flash']); endif; ?>

  <div class="card">
    <h2>Edit Product</h2>
    <form method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label>Product Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
      </div>
      <div class="form-group">
        <label>Brand</label>
        <input type="text" name="brand" value="<?= htmlspecialchars($product['brand']) ?>" required>
      </div>
      <div class="form-group">
        <label>Price</label>
        <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price']) ?>" required>
      </div>
      <div class="form-group">
        <label>Stock</label>
        <input type="number" name="stock" value="<?= htmlspecialchars($product['stock']) ?>" required>
      </div>
      <div class="preview">
        <label>Current Image</label><br>
        <img src="<?= htmlspecialchars($product['image']) ?>" alt="Product Image">
      </div>
      <div class="form-group">
        <label>Upload New Image</label>
        <input type="file" name="image" accept="image/*">
      </div>
      <div class="actions">
        <button type="submit" name="update_product" class="btn btn-save">Save</button>
        <a href="products.php" class="btn btn-cancel">Cancel</a>
      </div>
    </form>
  </div>
</body>
</html>

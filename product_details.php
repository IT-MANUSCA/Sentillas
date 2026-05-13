<?php
session_start();
include 'db.php';

if (!isset($_GET['id'])) {
    header('Location: shop.php');
    exit();
}

$product_id = intval($_GET['id']);

// Get product info
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

// Get product description info
$stmt = $pdo->prepare("SELECT * FROM product_descriptions WHERE product_id = ?");
$stmt->execute([$product_id]);
$details = $stmt->fetch();

if (!$product) {
    echo "Product not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($product['name']) ?> - Product Details</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background: #f4f4f4;
      margin: 0;
      padding: 40px 20px;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
    }

    .container {
      max-width: 1100px;
      width: 100%;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      display: flex;
      flex-wrap: wrap;
      overflow: hidden;
    }

    .image-section {
      flex: 1;
      min-width: 300px;
      background: #fafafa;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .image-section img {
      width: 100%;
      max-width: 450px;
      max-height: 450px;
      object-fit: contain;
      border-radius: 10px;
    }

    .details-section {
      flex: 1.2;
      min-width: 300px;
      padding: 30px;
    }

    h2 {
      margin-bottom: 10px;
      font-size: 1.8rem;
      color: #333;
    }

    .brand {
      color: #777;
      margin-bottom: 10px;
    }

    .price {
      color: #27ae60;
      font-weight: bold;
      font-size: 1.5rem;
      margin: 15px 0;
    }

    .stock {
      margin-bottom: 20px;
      color: #555;
    }

    .info-block {
      margin-bottom: 20px;
    }

    .info-block h3 {
      margin-bottom: 8px;
      color: #444;
      font-size: 1.1rem;
      border-left: 4px solid #6c63ff;
      padding-left: 8px;
    }

    #specs-block { background: #fdf7f7; padding: 10px; border-radius: 6px; }
    #features-block { background: #f7fdf7; padding: 10px; border-radius: 6px; }
    #warranty-block { background: #f7f7fd; padding: 10px; border-radius: 6px; }
    #info-block { background: #fffbe7; padding: 10px; border-radius: 6px; }

    #cart-button-block {
      margin-top: 25px;
      text-align: left;
    }

    #cart-button-block .btn {
      background: black;
      color: white;
      padding: 10px 20px;
      border: 1px solid transparent;
      border-radius: 6px;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    #cart-button-block .btn:hover {
      background: white;
      color: black;
      border: 1px solid black;
    }

    /* ✅ Mobile Responsive */
    @media (max-width: 768px) {
      body {
        padding: 20px 10px;
      }

      .container {
        flex-direction: column;
      }

      .image-section,
      .details-section {
        width: 100%;
        padding: 20px;
      }

      .image-section img {
        max-width: 100%;
        height: auto;
      }

      h2 {
        font-size: 1.5rem;
      }

      .price {
        font-size: 1.3rem;
      }

      .info-block h3 {
        font-size: 1rem;
      }

      #cart-button-block {
        text-align: center;
      }

      #cart-button-block .btn {
        width: 100%;
        max-width: 300px;
      }
    }
  </style>
</head>
<body>

<div class="container">
  <!-- Product Image -->
  <div class="image-section">
    <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
  </div>

  <!-- Product Details -->
  <div class="details-section">
    <h2><?= htmlspecialchars($product['name']) ?></h2>
    <p class="brand"><strong>Brand:</strong> <?= htmlspecialchars($product['brand']) ?></p>
    <p class="price">₱<?= number_format($product['price'], 2) ?></p>
    <p class="stock"><strong>Stock:</strong> <?= $product['stock'] ?></p>

    <?php if ($details): ?>
      <div class="info-block" id="specs-block">
        <h3>Specifications</h3>
        <p><?= nl2br(htmlspecialchars($details['specs'])) ?></p>
      </div>
      <div class="info-block" id="features-block">
        <h3>Features</h3>
        <p><?= nl2br(htmlspecialchars($details['features'])) ?></p>
      </div>
      <div class="info-block" id="warranty-block">
        <h3>Warranty</h3>
        <p><?= nl2br(htmlspecialchars($details['warranty'])) ?></p>
      </div>
      <div class="info-block" id="info-block">
        <h3>Additional Info</h3>
        <p><?= nl2br(htmlspecialchars($details['additional_info'])) ?></p>
      </div>
    <?php endif; ?>

    <!-- Add to Cart button -->
    <div id="cart-button-block">
      <a href="add_to_cart.php?product_id=<?= $product['id'] ?>" class="btn">Add to Cart</a>
    </div>
  </div>
</div>

</body>
</html>

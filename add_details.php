<?php
session_start();
include 'db.php'; // This must define $pdo

$product_id = intval($_GET['product_id'] ?? 0);

// Redirect if no valid product ID
if ($product_id <= 0) {
    $_SESSION['flash'] = ['message' => 'Invalid product ID.', 'type' => 'error'];
    header("Location: products.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $specs = $_POST['specs'];
    $features = $_POST['features'];
    $warranty = $_POST['warranty'];
    $additional_info = $_POST['additional_info'];

    // Check if there's already a description
    $stmt = $pdo->prepare("SELECT id FROM product_descriptions WHERE product_id = :product_id");
    $stmt->execute([':product_id' => $product_id]);

    if ($stmt->rowCount() > 0) {
        // Update existing description
        $update = $pdo->prepare("UPDATE product_descriptions 
                                SET specs = :specs, features = :features, warranty = :warranty, additional_info = :additional_info 
                                WHERE product_id = :product_id");
        $update->execute([
            ':specs' => $specs,
            ':features' => $features,
            ':warranty' => $warranty,
            ':additional_info' => $additional_info,
            ':product_id' => $product_id
        ]);
    } else {
        // Insert new description
        $insert = $pdo->prepare("INSERT INTO product_descriptions (product_id, specs, features, warranty, additional_info)
                                VALUES (:product_id, :specs, :features, :warranty, :additional_info)");
        $insert->execute([
            ':product_id' => $product_id,
            ':specs' => $specs,
            ':features' => $features,
            ':warranty' => $warranty,
            ':additional_info' => $additional_info
        ]);
    }

    $_SESSION['flash'] = ['message' => 'Details updated!', 'type' => 'success'];
    header("Location: products.php");
    exit();
}

// Get current product description (if any)
$stmt = $pdo->prepare("SELECT * FROM product_descriptions WHERE product_id = :product_id");
$stmt->execute([':product_id' => $product_id]);
$desc = $stmt->fetch();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Add Product Details</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f9f9f9; }
    form { background: #fff; padding: 20px; max-width: 600px; margin: auto; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    textarea { width: 100%; padding: 10px; margin: 10px 0; resize: vertical; border: 1px solid #ccc; border-radius: 6px; }
    button { background: #1a1a2e; color: #fff; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; }
    button:hover { background: #16213e; }
    h2 { text-align: center; margin-bottom: 20px; }
  </style>
</head>
<body>

<h2>Add Product Details</h2>

<form method="POST">
  <label>Specifications:</label>
  <textarea name="specs" rows="4"><?= htmlspecialchars($desc['specs'] ?? '') ?></textarea>

  <label>Features:</label>
  <textarea name="features" rows="4"><?= htmlspecialchars($desc['features'] ?? '') ?></textarea>

  <label>Warranty:</label>
  <textarea name="warranty" rows="2"><?= htmlspecialchars($desc['warranty'] ?? '') ?></textarea>

  <label>Additional Information:</label>
  <textarea name="additional_info" rows="3"><?= htmlspecialchars($desc['additional_info'] ?? '') ?></textarea>

  <button type="submit">Save Details</button>
</form>

</body>
</html>

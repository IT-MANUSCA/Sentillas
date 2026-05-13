<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Fetch product to check if it exists
    $check = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();

        // Delete image file (optional, only if not default)
        if ($product['image'] !== "uploads/default.png" && file_exists($product['image'])) {
            unlink($product['image']);
        }

        // Delete product record
        $delete = $conn->prepare("DELETE FROM products WHERE id = ?");
        $delete->bind_param("i", $id);

        if ($delete->execute()) {
            header("Location: products.php?msg=deleted");
            exit();
        } else {
            echo "❌ Error deleting product.";
        }
    } else {
        echo "⚠️ Product not found.";
    }
} else {
    echo "⚠️ Invalid request.";
}
?>

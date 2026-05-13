<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: view_cart.php");
    exit();
}

$cart_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Ensure the cart item belongs to the user
$stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
$stmt->execute([$cart_id, $user_id]);

header("Location: view_cart.php");
exit();
?>

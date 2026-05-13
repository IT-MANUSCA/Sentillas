<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status"=>"error", "message"=>"Not logged in"]);
    exit();
}

if (!isset($_POST['cart_id']) || !isset($_POST['quantity'])) {
    echo json_encode(["status"=>"error", "message"=>"Invalid request"]);
    exit();
}

$cart_id = intval($_POST['cart_id']);
$quantity = intval($_POST['quantity']);
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
if($stmt->execute([$quantity, $cart_id, $user_id])){
    echo json_encode(["status"=>"success"]);
} else {
    echo json_encode(["status"=>"error", "message"=>"Database update failed"]);
}

<?php
session_start();
include 'db.php';

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    header("Location: cart.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info (including address)
$stmt = $pdo->prepare("
    SELECT firstname, lastname, email, city, province 
    FROM users WHERE id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

$fullname = $user['firstname'] . ' ' . $user['lastname'];
$email    = $user['email'];
$city     = strtolower(trim($user['city'] ?? ''));
$province = strtolower(trim($user['province'] ?? ''));

$payment_method = $_POST['payment_method'] ?? '';
$proof = $_FILES['proof'] ?? null;

if (!$payment_method || !$proof || $proof['error'] !== 0) {
    header("Location: cart.php");
    exit();
}

/* ===========================
   DELIVERY FEE LOGIC
=========================== */
$delivery_fee = 0;

if (in_array($city, ['meycauayan','marilao','bocaue','santa maria']) && $province === 'bulacan') {
    $delivery_fee = 500;
} elseif (in_array($city, ['san mateo','montalban','rodriguez']) && $province === 'rizal') {
    $delivery_fee = 500;
} elseif (
    ($province === 'bulacan' && in_array($city, ['malolos','guiguinto','plaridel','baliuag','san rafael'])) ||
    ($province === 'rizal' && in_array($city, ['antipolo','cainta','taytay','binangonan','teresa','cardona','morong'])) ||
    ($province === 'laguna' && in_array($city, ['biñan','san pedro','santa rosa']))
) {
    $delivery_fee = 1000;
} elseif ($province === 'cavite' && in_array($city, ['bacoor','imus','dasmariñas'])) {
    $delivery_fee = 1500;
} elseif ($province === 'rizal' && in_array($city, ['tanay','jala-jala','pililla','baras'])) {
    $delivery_fee = 2000;
} elseif ($province === 'cavite' && in_array($city, ['tagaytay','silang','amadeo'])) {
    $delivery_fee = 2500;
} elseif ($province === 'laguna' && in_array($city, ['cabuyao','calamba','los baños'])) {
    $delivery_fee = 1500;
}

/* ===========================
   FILE UPLOAD
=========================== */
$allowed_exts = ['jpg','jpeg','png','pdf'];
$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

$originalName = preg_replace("/[^a-zA-Z0-9\.\-_]/", "", basename($proof['name']));
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($ext, $allowed_exts) || $proof['size'] > 5 * 1024 * 1024) {
    header("Location: cart.php");
    exit();
}

$filename = uniqid('proof_', true) . '.' . $ext;
$target_path = $upload_dir . $filename;

if (!move_uploaded_file($proof['tmp_name'], $target_path)) {
    error_log("File upload failed for user ID: $user_id");
    header("Location: cart.php");
    exit();
}

/* ===========================
   FETCH CART ITEMS
=========================== */
$stmt = $pdo->prepare("
    SELECT c.*, p.name AS product_name, p.price 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

if (count($cart_items) === 0) {
    header("Location: cart.php");
    exit();
}

/* ===========================
   SAVE PAYMENTS
=========================== */
$products_summary = "";
$is_first_item = true;

foreach ($cart_items as $item) {
    $product_name = $item['product_name'];
    $quantity     = $item['quantity'];
    $price        = $item['price'];

    $item_total = $price * $quantity;

    // Add delivery fee ONLY ONCE
    if ($is_first_item) {
        $item_total += $delivery_fee;
        $is_first_item = false;
    }

    $products_summary .= "
        $product_name (x$quantity) - ₱" . number_format($item_total, 2) . "<br>
    ";

    $insert = $pdo->prepare("
        INSERT INTO payments 
            (user_id, fullname, email, product_name, quantity, total_amount, payment_method, proof_of_payment)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $insert->execute([
        $user_id,
        $fullname,
        $email,
        $product_name,
        $quantity,
        $item_total,
        $payment_method,
        $target_path
    ]);
}

/* ===========================
   CLEAR CART
=========================== */
$pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$user_id]);

/* ===========================
   EMAIL CONFIRMATION
=========================== */
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'aquinojenesis1@gmail.com';
    $mail->Password   = 'xrpg gcde xgwk ccan';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('aquinojenesis1@gmail.com', 'Sentillas Aircon Supplies');
    $mail->addAddress($email, $fullname);

    $mail->isHTML(true);
    $mail->Subject = 'Payment Received - Order Acknowledgement';
    $mail->Body = "
        <h2>Thank you for your purchase, $fullname!</h2>
        <p>Payment Method: <strong>$payment_method</strong></p>
        <p><strong>Order Summary:</strong></p>
        $products_summary
        <p><strong>Delivery Fee:</strong> ₱" . number_format($delivery_fee, 2) . "</p>
        <p>Your payment will be verified shortly.</p>
        <p>– Sentillas Air-Conditioning Supplies and Services</p>
    ";

    $mail->send();
} catch (Exception $e) {
    error_log("Mailer Error: {$mail->ErrorInfo}");
}

header("Location: thankyou.php");
exit();
?>

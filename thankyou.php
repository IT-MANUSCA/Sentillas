<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's name + phone number
$stmt = $pdo->prepare("SELECT firstname, lastname, cellphone_number, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

$fullname = $user['firstname'] . ' ' . $user['lastname'];
$phone = $user['cellphone_number'];
$email = $user['email'];

/* ------------------------ SEND EMAIL ------------------------ */
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

try {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'aquinojenesis1@gmail.com';
    $mail->Password = 'xrpg gcde xgwk ccan'; // Gmail App Password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('aquinojenesis1@gmail.com', 'Sentillas Aircon');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = "Payment Received";
    $mail->Body = "
        Hello <b>$fullname</b>,<br><br>
        Thank you for your payment. Your transaction is now being verified.<br><br>
        - Sentillas Air-Conditioning Supplies and Services
    ";

    $mail->send();
} catch (Exception $e) {
    // Email fails silently but page continues
}

/* ------------------------ SEND SMS ------------------------ */
$semaphore_api_key = "24b4949fd7a2ad804aa23f95a28e5acc";
$sender_name = "Sentillas";

if (!empty($phone)) {

    // Convert 09XXXXXXXXX → 639XXXXXXXXX
    if (preg_match('/^09\d{9}$/', $phone)) {
        $phone = "63" . substr($phone, 1);
    }

    $sms_message = "Hi $fullname, your payment has been received and is now being verified. Thank you for trusting Sentillas Air-Conditioning Supplies and Services.";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.semaphore.co/api/v4/messages");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'apikey' => $semaphore_api_key,
        'number' => $phone,
        'message' => $sms_message,
        'sendername' => $sender_name
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Thank You</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #f9fafb; color: #333; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 40px 20px; }
        .thankyou-wrapper { background: #fff; padding: 50px 40px; border-radius: 14px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); max-width: 600px; width: 100%; text-align: center; }
        .thankyou-wrapper h1 { font-size: 2rem; color: #111827; margin-bottom: 20px; font-weight: 700; }
        .thankyou-wrapper p { font-size: 1rem; color: #374151; margin-bottom: 10px; }
        .thankyou-wrapper strong { color: #111827; }
        .thankyou-actions { margin-top: 30px; }
        .thankyou-actions a { background: black; color: white; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; margin: 10px; display: inline-block; transition: all 0.3s ease; border: 1px solid transparent; }
        .thankyou-actions a:hover { background: white; color: black; border: 1px solid black; }
    </style>
</head>
<body>

<div class="thankyou-wrapper">
    <h1>🎉 Thank You, <?= htmlspecialchars($fullname) ?>!</h1>
    <p>Your payment has been received and is now being verified.</p>
    <p>We appreciate your trust in <strong>Sentillas Air-Conditioning Supplies and Services</strong>.</p>
    <div class="thankyou-actions">
        <a href="home.php">Back to Home</a>
        <a href="to_receive.php">Track My Order</a>
    </div>
</div>

</body>
</html>

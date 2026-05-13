<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'db.php'; // ✅ use db.php (PDO)
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['email'])) {
    echo "<script>alert('No email found in session. Please register again.'); window.location.href='register.php';</script>";
    exit();
}

$email = $_SESSION['email'];

// Generate new verification code
$verification_code = mt_rand(100000, 999999);

// ✅ Update code in database (PDO)
$stmt = $pdo->prepare("UPDATE users SET verification_code = ? WHERE email = ?");
$stmt->execute([$verification_code, $email]);

// ✅ Get user’s phone number (PDO)
$stmt2 = $pdo->prepare("SELECT cellphone_number FROM users WHERE email = ?");
$stmt2->execute([$email]);
$user = $stmt2->fetch(PDO::FETCH_ASSOC);
$cellphone_number = $user['cellphone_number'] ?? null;

// ✅ Send Email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'aquinojenesis1@gmail.com';  
    $mail->Password = 'xrpg gcde xgwk ccan';  // app password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('aquinojenesis1@gmail.com', 'Sentillas AC System');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Resend: Verify Your Account - Sentillas AC System';
    $mail->Body = "
        Hi,<br><br>
        Your new verification code is: <b>$verification_code</b><br><br>
        Please enter this code to verify your account.
    ";

    $mail->send();
} catch (Exception $e) {
    error_log("Email error: {$mail->ErrorInfo}");
}

// ✅ Send SMS (only if number exists)
if ($cellphone_number) {
    $apiKey = "24b4949fd7a2ad804aa23f95a28e5acc"; // your Semaphore API key
    $senderName = "Sentillas"; // temporary until approved
    $smsMessage = "Your Sentillas verification code is: $verification_code";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.semaphore.co/api/v4/messages");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'apikey' => $apiKey,
        'number' => $cellphone_number,
        'message' => $smsMessage,
        'sendername' => $senderName
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $output = curl_exec($ch);
    if ($output === false) {
        error_log("SMS failed: " . curl_error($ch));
    }
    curl_close($ch);
}

echo "<script>alert('A new verification code has been sent to your Email and SMS. Please check.'); window.location.href='verify_email.php';</script>";
?>

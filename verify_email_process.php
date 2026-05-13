<?php
session_start();
require 'db.php';

if (!isset($_SESSION['email'])) {
    die("No session email found. Please log in or register again.");
}

$email = $_SESSION['email'];
$code = trim($_POST['verification_code'] ?? '');

if (empty($code)) {
    echo "<script>alert('⚠️ Please enter a verification code.'); window.location.href='verify_email.php';</script>";
    exit;
}

// Check if code matches
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND verification_code = ? AND is_verified = 0");
$stmt->execute([$email, $code]);
$user = $stmt->fetch();

if ($user) {
    // Mark as verified
    $update = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE email = ?");
    $update->execute([$email]);

    // Clear session
    unset($_SESSION['email']);

    echo "<script>alert('✅ Verification successful! You can now log in.'); window.location.href='login.php';</script>";
} else {
    echo "<script>alert('❌ Invalid code. Please try again.'); window.location.href='verify_email.php';</script>";
}
?>

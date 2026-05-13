<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validate passwords match
    if ($password !== $confirmPassword) {
        die('Passwords do not match!');
    }

    // Password strength validation
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        die('Password does not meet requirements!');
    }

    // Check token
    $stmt = $pdo->prepare("SELECT email, reset_token_expiry FROM users WHERE reset_token = :token");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die('Invalid token!');
    }

    if (strtotime($user['reset_token_expiry']) < time()) {
        die('Token expired!');
    }

    // Hash new password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Update password and clear token
    $update = $pdo->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_token_expiry = NULL WHERE email = :email");
    $update->execute([
        ':password' => $hashedPassword,
        ':email' => $user['email']
    ]);

    echo "<script>alert('Password has been updated successfully. You can now login.'); window.location.href='login.php';</script>";
} else {
    die('Invalid request method.');
}
?>

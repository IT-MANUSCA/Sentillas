<?php
require 'db.php';

$message = '';
$success = false;

if (!isset($_GET['token'])) {
    $message = "Invalid link.";
} else {
    $token = $_GET['token'];

    $stmt = $pdo->prepare("SELECT id, username, is_confirmed FROM admin_users WHERE confirm_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $message = "Invalid or expired token.";
    } elseif ($user['is_confirmed'] == 1) {
        $message = "Admin <b>{$user['username']}</b> is already confirmed.";
    } else {
        $update = $pdo->prepare("UPDATE admin_users SET is_confirmed = 1, confirm_token = NULL WHERE id = ?");
        if($update->execute([$user['id']])) {
            $message = "Admin <b>{$user['username']}</b> has been successfully confirmed!";
            $success = true;
        } else {
            $message = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Confirmation</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter','Segoe UI',sans-serif; }

    body {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background: #f3f4f6;
    }

    .card {
        background: #fff;
        padding: 40px 30px;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        text-align: center;
        max-width: 450px;
        width: 100%;
        animation: fadeIn 0.6s ease;
    }

    .card h2 {
        font-size: 1.8rem;
        margin-bottom: 20px;
        color: #111827;
    }

    .message {
        display: inline-block;
        padding: 14px 18px;
        border-radius: 12px;
        font-size: 0.95rem;
        margin-bottom: 25px;
        font-weight: 500;
    }
    .message.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .message.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .message i { margin-right: 8px; }

    .btn {
        display: inline-block;
        padding: 12px 25px;
        font-size: 1rem;
        font-weight: 600;
        border-radius: 10px;
        border: none;
        color: #fff;
        background: linear-gradient(135deg,#2563eb,#1d4ed8);
        text-decoration: none;
        transition: 0.3s;
    }
    .btn:hover { background: linear-gradient(135deg,#1d4ed8,#1e40af); transform: translateY(-2px); }

    @keyframes fadeIn { from {opacity:0; transform:translateY(15px);} to {opacity:1; transform:translateY(0);} }
</style>
</head>
<body>

<div class="card">
    <div class="message <?= $success ? 'success' : 'error' ?>">
        <i class="fas <?= $success ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i> 
        <?= $message ?>
    </div>

    <?php if ($success): ?>
        <a href="admin_login.php" class="btn"><i class="fas fa-sign-in-alt"></i> Go to Login</a>
    <?php endif; ?>
</div>

</body>
</html>

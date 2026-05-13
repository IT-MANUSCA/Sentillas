<?php
session_start();
require 'db.php'; // make sure $pdo is defined here

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $userId = $_SESSION['user_id'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Invalid email address.'];
        header('Location: contactus.php');
        exit();
    }

    try {
        // ✅ Check if email already subscribed
        $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) > 0) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'You are already subscribed with this email.'];
            header('Location: contactus.php');
            exit();
        }

        // ✅ Check how many emails this user already subscribed
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM newsletter_subscribers WHERE user_id = ?");
        $stmt->execute([$userId]);
        $countResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $countResult['total'] ?? 0;

        if ($count >= 3) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'You can only subscribe up to 3 emails.'];
            header('Location: contactus.php');
            exit();
        }

        // ✅ Insert new subscription
        $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (user_id, email) VALUES (?, ?)");
        if ($stmt->execute([$userId, $email])) {
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Thank you for subscribing!'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Something went wrong. Please try again later.'];
        }

        header('Location: contactus.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Database error. Please try again later.'];
        header('Location: contactus.php');
        exit();
    }
} else {
    header('Location: contactus.php');
    exit();
}

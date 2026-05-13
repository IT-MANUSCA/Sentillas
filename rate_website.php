<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $rating = (int) $_POST['rating'];

    if ($rating < 1 || $rating > 5) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'text' => 'Invalid rating value.'
        ];
        header("Location: home.php");
        exit();
    }

    // Check if user already rated
    $stmt = $pdo->prepare("SELECT * FROM website_ratings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetch()) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'text' => 'You have already rated the website.'
        ];
    } else {
        $insert = $pdo->prepare("INSERT INTO website_ratings (user_id, rating) VALUES (?, ?)");
        $insert->execute([$user_id, $rating]);

        $_SESSION['flash_message'] = [
            'type' => 'success',
            'text' => 'Thank you for rating our website!'
        ];
    }

    header("Location: home.php");
    exit();
} else {
    header("Location: home.php");
    exit();
}

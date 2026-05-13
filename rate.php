<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rating'])) {
    $user_id = $_SESSION['user_id'];
    $rating = intval($_POST['rating']);

    $stmt = $pdo->prepare("SELECT id FROM website_ratings WHERE user_id = ?");
    $stmt->execute([$user_id]);

    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("UPDATE website_ratings SET rating = ?, created_at = NOW() WHERE user_id = ?");
        $stmt->execute([$rating, $user_id]);
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Your rating has been updated!'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO website_ratings (user_id, rating) VALUES (?, ?)");
        $stmt->execute([$user_id, $rating]);
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Thank you for rating us!'];
    }
}

header('Location: home.php');
exit();
?>

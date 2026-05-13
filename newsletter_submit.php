<?php
session_start();
require 'db.php'; // Your DB connection

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';

    if (empty($title) || empty($content)) {
        die('Title and content are required.');
    }

    // Fetch all subscribers
    $stmt = $pdo->query("SELECT email FROM newsletter_subscribers");
    $subscribers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$subscribers) {
        die('No subscribers found.');
    }

    // Configure PHPMailer
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'aquinojenesis1@gmail.com'; // Your SMTP username
        $mail->Password   = 'xrpg gcde xgwk ccan';   // Your SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // or PHPMailer::ENCRYPTION_SMTPS
        $mail->Port       = 587; // SMTP port

        $mail->setFrom('aquinojenesis1@gmail.com', 'Sentillas Airconditioning');

        // Send to each subscriber
        foreach ($subscribers as $email) {
            $mail->clearAddresses();
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = $title;
            $mail->Body    = nl2br(htmlspecialchars($content));
            $mail->AltBody = strip_tags($content);

            $mail->send();
        }

           // If all emails sent successfully, redirect with success message
    header("Location: admin_newsletter.php?success=1");
    exit();

} catch (Exception $e) {
    // On error, redirect with error message
    header("Location: admin_newsletter.php?error=" . urlencode($mail->ErrorInfo));
    exit();
}

} else {
    die('Invalid request method.');
}

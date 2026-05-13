<?php
session_start();
require 'db.php';

// PHPMailer
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Semaphore SMS Settings
$semaphore_api_key = "24b4949fd7a2ad804aa23f95a28e5acc";
$sender_name = "Sentillas";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $identifier = trim($_POST['identifier']); // can be email or phone

    // ✅ Convert 09XXXXXXXXX → 639XXXXXXXXX (PH Number Format)
    if (preg_match('/^09\d{9}$/', $identifier)) {
        $identifier = "63" . substr($identifier, 1);
    }

    // ✅ Search by email OR cellphone number
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :id OR cellphone_number = :id");
    $stmt->execute([':id' => $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<script>alert('No account found with that Email or Phone Number.'); window.location.href='forgot_password.php';</script>";
        exit();
    }

    // Generate reset token & expiry (1 hour)
    $token = bin2hex(random_bytes(32));
    $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

    $update = $pdo->prepare("UPDATE users SET reset_token = :token, reset_token_expiry = :expiry WHERE id = :id");
    $update->execute([
        ':token' => $token,
        ':expiry' => $expiry,
        ':id' => $user['id']
    ]);

    // ✅ Use live domain instead of localhost
    $resetLink = "https://sentillas.shop/reset_password.php?token=$token";

    // ================= SEND EMAIL =================
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aquinojenesis1@gmail.com';
        $mail->Password = 'xrpg gcde xgwk ccan'; // Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('aquinojenesis1@gmail.com', 'Sentillas Aircon');
        $mail->addAddress($user['email']);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body = "
            Hello <b>{$user['firstname']}</b>,<br><br>
            A request was made to reset your password.<br><br>
            Click the link below to reset it:<br>
            <a href='$resetLink'>$resetLink</a><br><br>
            This link will expire in 1 hour.<br><br>
            - Sentillas Aircon
        ";

        $mail->send();

    } catch (Exception $e) {
        echo "<script>alert('Email sending failed.'); window.location.href='forgot_password.php';</script>";
        exit();
    }

    // ================= SEND SMS =================
    $sms_message = "Hi {$user['firstname']}, a password reset link was sent to your email. - Sentillas Aircon";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.semaphore.co/api/v4/messages");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'apikey' => $semaphore_api_key,
        'number' => $user["cellphone_number"],
        'message' => $sms_message,
        'sendername' => $sender_name
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);

    echo "<script>alert('Password reset link sent! Check your email & SMS.'); window.location.href='login.php';</script>";
}
?>

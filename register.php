<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$semaphore_api_key = "24b4949fd7a2ad804aa23f95a28e5acc";
$password_error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $username = trim($_POST['username']);
    $cellphone_number = trim($_POST['cellphone_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $street_address = trim($_POST['street_address']);
    $city = trim($_POST['city']);
    $province = trim($_POST['province']);
    $address = $city . ', ' . $province;

    if ($password !== $confirm_password) {
        $password_error = 'Passwords do not match.';
    }

    function formatPhoneNumber($number) {
        $number = preg_replace('/\D/', '', $number);
        if (strpos($number, '09') === 0) {
            return '63' . substr($number, 1);
        }
        if (strpos($number, '63') === 0 && strlen($number) === 12) {
            return $number;
        }
        return $number;
    }

    $cellphone_number = formatPhoneNumber($cellphone_number);

    if (!preg_match('/^63\d{10}$/', $cellphone_number)) {
        echo "<script>alert('Please enter a valid cellphone number starting with 09XXXXXXXXX.'); window.location.href='register.php';</script>";
        exit;
    }

    $passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
    if (!preg_match($passwordPattern, $password)) {
        echo "<script>alert('Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character.'); window.location.href='register.php';</script>";
        exit;
    }

    $check = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $check->execute([$email, $username]);
    if ($check->fetch()) {
        echo "<script>alert('Email or Username already registered.'); window.location.href='register.php';</script>";
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $verification_code = mt_rand(100000, 999999);

    $stmt = $pdo->prepare("INSERT INTO users 
        (firstname, lastname, email, username, cellphone_number, password, city, province, address, verification_code, is_verified) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");

    $success = $stmt->execute([
        $firstname, $lastname, $email, $username, $cellphone_number, $hashedPassword,
        $city, $province, $address, $verification_code
    ]);

    if ($success) {
        $_SESSION['email'] = $email;

        // ✅ Send SMS via Semaphore
        // ✅ Send SMS via Semaphore
$number = $cellphone_number;
$sendername = "Sentillas"; // ✅ MUST MATCH APPROVED SENDER NAME EXACTLY
$message = "Hi $firstname, thank you for registering at Sentillas Aircon. Your verification code is: $verification_code";

$url = "https://api.semaphore.co/api/v4/messages";
$fields = [
    'apikey' => $semaphore_api_key,
    'number' => $number,
    'message' => $message,
    'sendername' => $sendername
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

// ✅ Log response for debugging
file_put_contents("sms_logs.txt", date("Y-m-d H:i:s") . " - " . $response . "\n", FILE_APPEND);

        // ✅ Send verification email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'aquinojenesis1@gmail.com';
            $mail->Password = 'xrpg gcde xgwk ccan'; // app password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('aquinojenesis1@gmail.com', 'Sentillas Aircon');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email - Sentillas Aircon';
            $mail->Body = "
                Hi $firstname $lastname,<br><br>
                Thank you for registering at Sentillas Aircon.<br>
                Your verification code is: <b>$verification_code</b><br><br>
                Please enter this code to verify your email.
            ";
            $mail->send();
            echo "<script>alert('Registration successful! Check your SMS and Email for the verification code.'); window.location.href='verify_email.php';</script>";
        } catch (Exception $e) {
            echo "<script>alert('Registration successful, but email sending failed.'); window.location.href='verify_email.php';</script>";
            error_log("Email error: {$mail->ErrorInfo}");
        }
    } else {
        echo "<script>alert('Registration failed. Please try again.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:"Poppins",sans-serif; }
    body { min-height:100vh; display:flex; justify-content:center; align-items:center; background-image: url(bg.png); }
    .form { background:#fff; padding:2.5rem 2rem; border-radius:18px; width:400px; box-shadow:0px 10px 30px rgba(0,0,0,0.15); animation:fadeIn 0.6s ease; }
    .form .title { font-size:26px; font-weight:600; margin-bottom:0.4rem; text-align:center; color:#222; }
    .form .message { text-align:center; font-size:14px; color:#666; margin-bottom:2rem; }
    .form label { display:block; margin-bottom:1.6rem; position:relative; }
    .form .flex { display:flex; gap:12px; }
    .input { width:100%; padding:14px; border:1.5px solid #ddd; border-radius:12px; outline:none; font-size:14px; transition:0.3s; background:#fafafa; }
    .input:focus { border-color:#6c63ff; background:#fff; box-shadow:0px 4px 8px rgba(108,99,255,0.15);}
    label span { position:absolute; top:14px; left:16px; color:#888; font-size:14px; pointer-events:none; transition:0.2s; }
    .input:focus + span, .input:not(:placeholder-shown) + span { top:-8px; left:12px; background:#fff; font-size:12px; color:#6c63ff; padding:0 4px; }
    small { display:block; margin-top:-12px; margin-bottom:12px; color:#999; font-size:12px; }
    .submit { width:100%; padding:14px; background:linear-gradient(135deg,#6c63ff,#9a8cff); border:none; border-radius:12px; font-size:16px; font-weight:500; color:#fff; cursor:pointer; transition:all 0.25s ease; }
    .submit:hover { transform:translateY(-2px); box-shadow:0px 6px 15px rgba(108,99,255,0.3); }
    .signin { text-align:center; margin-top:1.2rem; font-size:14px; }
    .signin a { color:#6c63ff; text-decoration:none; font-weight:500; }
    .signin a:hover { text-decoration:underline; }
    @keyframes fadeIn { from {opacity:0; transform:translateY(15px);} to {opacity:1; transform:translateY(0);} }
  </style>
</head>
<body>
<form class="form" method="POST" action="register.php">
    <p class="title">Create Account</p>
    <p class="message">Sign up now and enjoy full access</p>

    <div class="flex">
      <label>
        <input required name="firstname" type="text" class="input" placeholder=" " value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>">
        <span>Firstname</span>
      </label>
      <label>
        <input required name="lastname" type="text" class="input" placeholder=" " value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>">
        <span>Lastname</span>
      </label>
    </div>

    <label>
      <input required name="email" type="email" class="input" placeholder=" " value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
      <span>Email</span>
    </label>

    <label>
      <input required name="username" type="text" class="input" placeholder=" " value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
      <span>Username</span>
    </label>
    <small>Username must be unique.</small>

    <label>
      <input required name="cellphone_number" type="text" class="input" placeholder=" " value="<?php echo isset($_POST['cellphone_number']) ? htmlspecialchars($_POST['cellphone_number']) : ''; ?>">
      <span>Cellphone Number</span>
    </label>
    <small>Enter a valid 11-digit mobile number (e.g., 09123456789). It will be stored in +63 format.</small>

    <label>
      <input required name="street_address" type="text" class="input" placeholder=" " value="<?php echo isset($_POST['street_address']) ? htmlspecialchars($_POST['street_address']) : ''; ?>">
      <span>Street Address</span>
    </label>

    <div class="flex">
      <label>
        <input required name="city" type="text" class="input" placeholder=" " value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
        <span>City</span>
      </label>
      <label>
        <input required name="province" type="text" class="input" placeholder=" " value="<?php echo isset($_POST['province']) ? htmlspecialchars($_POST['province']) : ''; ?>">
        <span>Province</span>
      </label>
    </div>

    <label>
      <input required name="password" type="password" class="input" placeholder=" ">
      <span>Password</span>
    </label>
    <small>Password must include uppercase, lowercase, number & special character.</small>

    <label>
      <input required name="confirm_password" type="password" class="input" placeholder=" ">
      <span>Confirm Password</span>
    </label>
    <?php if (!empty($password_error)): ?>
      <small style="color: red;"><?php echo $password_error; ?></small>
    <?php endif; ?>

    <button class="submit" type="submit">Register</button>
    <p class="signin">Already have an account? <a href="index.php">Sign in</a></p>
</form>
</body>
</html>

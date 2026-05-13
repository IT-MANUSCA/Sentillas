<?php
session_start();
require 'db.php';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die('No token provided in URL!');
}

$token = $_GET['token'];

// Check token existence and expiry
$stmt = $pdo->prepare("SELECT email, reset_token_expiry FROM users WHERE reset_token = :token");
$stmt->execute([':token' => $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Token does not exist!');
}

if (strtotime($user['reset_token_expiry']) < time()) {
    die('Token expired!');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap">
  <style>
    /* Reset */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Poppins", sans-serif;
    }

    body {
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    }

    /* Form Card */
    .form {
      background: #fff;
      padding: 2.5rem 2rem;
      border-radius: 18px;
      width: 420px;
      box-shadow: 0px 10px 30px rgba(0,0,0,0.15);
      animation: fadeIn 0.6s ease;
    }

    .form .title {
      font-size: 26px;
      font-weight: 600;
      margin-bottom: 0.4rem;
      text-align: center;
      color: #222;
    }

    .form .message {
      text-align: center;
      font-size: 14px;
      color: #666;
      margin-bottom: 2rem;
    }

    /* Input Group */
    .form label {
      display: block;
      margin-bottom: 1.6rem;
      position: relative;
    }

    .input {
      width: 100%;
      padding: 14px;
      border: 1.5px solid #ddd;
      border-radius: 12px;
      outline: none;
      font-size: 14px;
      transition: 0.3s;
      background: #fafafa;
    }

    .input:focus {
      border-color: #6c63ff;
      background: #fff;
      box-shadow: 0px 4px 8px rgba(108, 99, 255, 0.15);
    }

    label span {
      position: absolute;
      top: 14px;
      left: 16px;
      color: #888;
      font-size: 14px;
      pointer-events: none;
      transition: 0.2s;
    }

    .input:focus + span,
    .input:not(:placeholder-shown) + span {
      top: -8px;
      left: 12px;
      background: #fff;
      font-size: 12px;
      color: #6c63ff;
      padding: 0 4px;
    }

    /* Password strength */
    #password-strength {
      font-size: 13px;
      margin: -12px 0 12px 4px;
    }

    /* Password tips */
    .password-tips {
      background: #f9f9f9;
      padding: 10px 14px;
      border-radius: 10px;
      margin-bottom: 1.5rem;
      font-size: 13px;
      color: #555;
    }

    .password-tips h4 {
      margin-bottom: 6px;
      font-size: 14px;
      color: #333;
    }

    .password-tips ul {
      list-style: disc;
      padding-left: 20px;
    }

    .password-tips li {
      margin-bottom: 4px;
    }

    /* Button */
    .submit {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #6c63ff, #9a8cff);
      border: none;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 500;
      color: #fff;
      cursor: pointer;
      transition: all 0.25s ease;
    }

    .submit:hover {
      transform: translateY(-2px);
      box-shadow: 0px 6px 15px rgba(108, 99, 255, 0.3);
    }

    /* Animation */
    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(15px);}
      to {opacity: 1; transform: translateY(0);}
    }
  </style>
  <script>
    function validatePasswords() {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;

      if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return false;
      }

      const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
      if (!passwordRegex.test(password)) {
        alert('Password does not meet requirements!');
        return false;
      }
      return true;
    }

    function checkPasswordStrength() {
      const password = document.getElementById('password').value;
      const strengthMsg = document.getElementById('password-strength');
      const requirements = [/.{8,}/, /[a-z]/, /[A-Z]/, /\d/, /[\W_]/];
      const passed = requirements.filter((regex) => regex.test(password)).length;

      switch (passed) {
        case 5:
          strengthMsg.innerText = 'Strong Password';
          strengthMsg.style.color = 'green';
          break;
        case 4:
          strengthMsg.innerText = 'Medium - Consider adding a symbol or number';
          strengthMsg.style.color = 'orange';
          break;
        default:
          strengthMsg.innerText = 'Weak - Follow all requirements';
          strengthMsg.style.color = 'red';
      }
    }
  </script>
</head>
<body>
  <form class="form" action="reset_password_process.php" method="POST" onsubmit="return validatePasswords()">
    <p class="title">Reset Password</p>
    <p class="message">Enter your new password and confirm it below.</p>

    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

    <label>
      <input type="password" id="password" name="password" class="input" required placeholder=" " oninput="checkPasswordStrength()">
      <span>New Password</span>
    </label>
    <p id="password-strength"></p>

    <label>
      <input type="password" id="confirm_password" name="confirm_password" class="input" required placeholder=" ">
      <span>Confirm Password</span>
    </label>

    <div class="password-tips">
      <h4>Password Requirements:</h4>
      <ul>
        <li>At least 8 characters</li>
        <li>At least 1 uppercase letter</li>
        <li>At least 1 lowercase letter</li>
        <li>At least 1 number</li>
        <li>At least 1 special character (e.g., !@#$%^&*)</li>
      </ul>
    </div>

    <button type="submit" class="submit">Update Password</button>
  </form>
</body>
</html>
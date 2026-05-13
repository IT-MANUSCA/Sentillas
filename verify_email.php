<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
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
      width: 400px;
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

    /* Links */
    .signin {
      text-align: center;
      margin-top: 1.2rem;
      font-size: 14px;
    }

    .signin a {
      color: #6c63ff;
      text-decoration: none;
      font-weight: 500;
    }

    .signin a:hover {
      text-decoration: underline;
    }

    /* Animation */
    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(15px);}
      to {opacity: 1; transform: translateY(0);}
    }
    </style>
</head>
<body>

<form class="form" action="verify_email_process.php" method="POST">
    <p class="title">Email Verification</p>
    <p class="message">Please enter the 6-digit code sent to your Email or Phone.</p>

    <label>
        <input type="text" id="verification_code" name="verification_code" class="input" required maxlength="6" placeholder=" ">
        <span>Verification Code</span>
    </label>

    <button type="submit" class="submit">Verify</button>

    <p class="signin">Didn't receive the code? <a href="resend_verification.php">Resend Code</a></p>
</form>

</body>
</html>

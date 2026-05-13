<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap">
  <style>
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
      text-align: center;
      color: #222;
      margin-bottom: 0.4rem;
    }

    .form .message {
      text-align: center;
      font-size: 14px;
      color: #666;
      margin-bottom: 2rem;
    }

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
      background: #fafafa;
      outline: none;
      transition: 0.3s;
      font-size: 14px;
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

    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(15px);}
      to {opacity: 1; transform: translateY(0);}
    }
  </style>
</head>
<body>

  <form class="form" action="forgot_password_process.php" method="POST">
    <p class="title">Forgot Password</p>
    <p class="message">Enter your email or phone number to reset your password.</p>

    <label>
      <input type="text" name="identifier" class="input" required placeholder=" ">
      <span>Email or Phone Number</span>
    </label>

    <button type="submit" class="submit">Send Reset Link</button>
  </form>

</body>
</html>

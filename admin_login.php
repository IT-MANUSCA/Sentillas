<?php
session_name("admin_session");
session_start();
require 'db.php';
require 'db.php';

$error = '';
$error_type = '';
$error_icon = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = "User not found.";
        $error_type = 'error';
        $error_icon = 'fa-circle-xmark';
    } elseif (!password_verify($password, $user['password'])) {
        $error = "Incorrect password.";
        $error_type = 'error';
        $error_icon = 'fa-circle-xmark';
    } elseif ($user['is_confirmed'] == 0) {
        $error = "Your account is not confirmed by the owner yet.";
        $error_type = 'error';
        $error_icon = 'fa-circle-exclamation';
    } else {
        // Successful login
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_logged_in'] = true;
        header("Location: dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body {
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}
.login-box {
    background:#fff;
    padding:40px 30px;
    border-radius:14px;
    box-shadow:0 6px 20px rgba(0,0,0,0.15);
    width:100%;
    max-width:400px;
    position:relative;
    animation:fadeIn 0.6s ease;
}
h2 {
    text-align:center;
    margin-bottom:25px;
    color:#1a1a2e;
}
.form-group { margin-bottom:18px; }
label { display:block; margin-bottom:6px; font-weight:500; color:#333; }
input { width:100%; padding:12px 14px; border:1px solid #ccc; border-radius:8px; font-size:1rem; }
input:focus { border-color:#1a1a2e; outline:none; }
button {
    width:100%; padding:12px; background:#1a1a2e; color:#fff;
    border:none; border-radius:8px; font-size:1rem; font-weight:bold; cursor:pointer; transition:0.3s;
}
button:hover { background:#16213e; }
/* Flash message styles */
.flash-message {
    margin-bottom: 15px;
    padding: 10px 15px;
    text-align: center;
    border-radius: 8px;
    font-size: 0.95rem;
    opacity: 1;
    transition: opacity 0.5s ease-out;
}
.flash-message i { margin-right:8px; }
.flash-message.success { background:#28a745; color:#fff; }
.flash-message.error { background:#dc3545; color:#fff; }

@keyframes fadeIn { from {opacity:0; transform:translateY(15px);} to {opacity:1; transform:translateY(0);} }
</style>
</head>
<body>

<div class="login-box">
    <h2><i class="fas fa-lock"></i> Admin Login</h2>

    <?php if (!empty($error)): ?>
        <div class="flash-message <?= $error_type ?>" id="flashMessage">
            <i class="fas <?= $error_icon ?>"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="username"><i class="fas fa-user"></i> Username</label>
            <input type="text" name="username" id="username" required autofocus value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
        </div>
        <div class="form-group">
            <label for="password"><i class="fas fa-key"></i> Password</label>
            <input type="password" name="password" id="password" required>
        </div>
        <button type="submit"><i class="fas fa-sign-in-alt"></i> Login</button>
    </form>
</div>

<script>
    // Auto hide flash message after 4 seconds
    const flashMessage = document.getElementById('flashMessage');
    if(flashMessage){
        setTimeout(() => { flashMessage.style.opacity = '0'; }, 4000);
        setTimeout(() => { flashMessage.remove(); }, 4500);
    }
</script>

</body>
</html>

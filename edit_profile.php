<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Count cart items
$cart_count = 0;
$stmt = $pdo->prepare("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$row = $stmt->fetch();
if ($row && $row['total_items']) {
    $cart_count = $row['total_items'];
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update Profile
    if (isset($_POST['update_profile'])) {
        $firstname        = trim($_POST['firstname']);
        $lastname         = trim($_POST['lastname']);
        $username         = trim($_POST['username']);
        $cellphone_number = trim($_POST['cellphone_number']); // FIXED
        $city             = trim($_POST['city']);
        $province         = trim($_POST['province']);
        $address          = trim($_POST['address']);

        $stmt = $pdo->prepare("UPDATE users 
                               SET firstname=?, lastname=?, username=?, cellphone_number=?, city=?, province=?, address=? 
                               WHERE id=?");
        $updated = $stmt->execute([$firstname, $lastname, $username, $cellphone_number, $city, $province, $address, $user_id]);

        if ($updated) {
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Profile updated successfully!'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Failed to update profile.'];
        }
        header('Location: edit_profile.php');
        exit();
    }

    // Change Password
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password     = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (!password_verify($current_password, $user['password'])) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Current password is incorrect.'];
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'New passwords do not match.'];
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
            $updated = $stmt->execute([$hashed_password, $user_id]);

            if ($updated) {
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Password changed successfully!'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Failed to change password.'];
            }
        }
        header('Location: edit_profile.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Profile - Sentillas Airconditioning</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        body {
            background-image: url(bg.png);
            color: #333;
            line-height: 1.6;
        }

    header {
      background: white;
      padding: 1rem 5%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: #333;
    }

    header h1 {
      font-size: 1.8rem;
      font-weight: 600;
      margin-right: 210px;
    }
    nav a {
        color: #333;
        margin: 0 10px;
        text-decoration: none;
        font-weight: 500;
        padding: 6px 10px;
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    nav a:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }
.container {
  padding: 2rem 5%;
  max-width: 1200px;
  margin: auto;
}

h2 {
  margin-bottom: 1rem;
  color: #222;
}

.flash-message {
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  padding: 12px 20px;
  border-radius: 6px;
  z-index: 9999;
  font-size: 14px;
  font-weight: bold;
  animation: fadeOut 0.5s ease-in-out 3s forwards;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.flash.success {
  background: #d4edda;
  color: #155724;
  border-left: 5px solid #28a745;
}

.flash.error {
  background: #f8d7da;
  color: #721c24;
  border-left: 5px solid #dc3545;
}

@keyframes fadeOut {
  to {
    opacity: 0;
    visibility: hidden;
  }
}

.address-reminder {
  margin-bottom: 1rem;
  padding: 10px;
  background: #fff9c4;
  border: 1px solid #ffe082;
  border-radius: 6px;
  font-size: 14px;
}

/* Forms Layout */
.forms-wrapper {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

form {
  background: #fff;
  padding: 1.5rem;
  border-radius: 10px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.08);
  flex: 1;
  min-width: 350px;
}

form label {
  font-weight: 500;
  margin-bottom: 4px;
  display: block;
  color: #444;
  font-size: 14px;
}

input {
  width: 100%;
  padding: 8px;
  margin-bottom: 0.8rem;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 14px;
}

button {
  background: black;
  color: white;
  padding: 8px 16px;
  border: 1px solid transparent;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 500;
  font-size: 14px;
}

button:hover {
  background: white;
  color: black;
  border: 1px solid black;
}

/* Modal Styles */
.modal {
  display: none;
  position: fixed;
  z-index: 9999;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0,0,0,0.5);
}

.modal-content {
  background-color: #fefefe;
  margin: 15% auto;
  padding: 20px;
  border-radius: 10px;
  width: 90%;
  max-width: 400px;
  text-align: center;
}

.modal-content p {
  margin-bottom: 20px;
  font-size: 16px;
}

.modal-content button {
  margin: 0 10px;
}

  /* Responsive Design */
    @media (max-width: 768px) {

  header {
    flex-direction: column;
    align-items: flex-start;
  }

  nav {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
  }

  nav a {
    padding: 8px 12px;
    font-size: 14px;
  }

  h1 {
    font-size: 1.2rem;
  }

  h2 {
    font-size: 1.2rem;
  }

  .forms-wrapper {
    flex-direction: column;
  }

  form {
    width: 100%;
  }

  input, label, button {
    font-size: 13px;
  }

  button {
    width: 100%;
  }
}
.logo-container {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ac {
    width: 50px;
    height: 50px;
    object-fit: contain;
}
</style>
</head>
<body>

<header>
    <div class="logo-container">
      <img src="ac.png" alt="Logo" class="ac">
    </div>
  <h1>Sentillas Airconditioning</h1>
  <nav>
    <a href="home.php"><i class="fas fa-home"></i> Home</a>
    <a href="contactus.php"><i class="fas fa-envelope"></i> Contact</a>
    <a href="shop.php"><i class="fas fa-store"></i> Shop</a>
    <a href="view_cart.php"><i class="fas fa-shopping-cart"></i> (<?= $cart_count ?>)</a>
    <a href="to_receive.php"><i class="fas fa-box"></i> My Orders</a>
    <a href="my_maintenance.php"><i class="fas fa-tools"></i> My Maintenance</a>
    <a href="edit_profile.php"><i class="fas fa-user"></i> Profile</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout (<?= $_SESSION['username']; ?>)</a>
  </nav>
</header>

<div class="container">

  <?php if (isset($_SESSION['flash'])): ?>
    <div class="flash-message flash <?= $_SESSION['flash']['type'] ?>">
      <?= $_SESSION['flash']['msg'] ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
  <?php endif; ?>

  <!-- Address Reminder -->
  <div class="address-reminder">
      <strong>Your Saved Address:</strong><br>
      <?= htmlspecialchars($user['city'] ?? '-') ?>, 
      <?= htmlspecialchars($user['province'] ?? '-') ?> <br>
      <em>(Full Address on file: <?= htmlspecialchars($user['address'] ?? '-') ?>)</em><br>
      <strong>Cellphone:</strong> <?= htmlspecialchars($user['cellphone_number'] ?? '-') ?> <!-- FIXED -->
  </div>

  <div class="forms-wrapper">
    <!-- Edit Profile Form -->
    <form id="profileForm" method="post">
      <input type="hidden" name="update_profile" value="1">
      <h2>Edit Profile</h2>
      <label for="firstname">First Name</label>
      <input type="text" name="firstname" value="<?= htmlspecialchars($user['firstname']); ?>" required>
      <label for="lastname">Last Name</label>
      <input type="text" name="lastname" value="<?= htmlspecialchars($user['lastname']); ?>" required>
      <label for="username">Username</label>
      <input type="text" name="username" value="<?= htmlspecialchars($user['username']); ?>" required>
      <label for="cellphone_number">Cellphone Number</label>
      <input type="text" name="cellphone_number" value="<?= htmlspecialchars($user['cellphone_number'] ?? ''); ?>" required> <!-- FIXED -->
      <label for="city">City</label>
      <input type="text" name="city" value="<?= htmlspecialchars($user['city']); ?>">
      <label for="province">Province</label>
      <input type="text" name="province" value="<?= htmlspecialchars($user['province']); ?>">
      <label for="address">Full Address</label>
      <input type="text" name="address" value="<?= htmlspecialchars($user['address']); ?>" required>
      <button type="button" onclick="confirmSave('profileForm')">Update Profile</button>
    </form>

    <!-- Change Password Form -->
    <form id="passwordForm" method="post">
      <input type="hidden" name="change_password" value="1">
      <h2>Change Password</h2>
      <label for="current_password">Current Password</label>
      <input type="password" name="current_password" required>
      <label for="new_password">New Password</label>
      <input type="password" name="new_password" required>
      <label for="confirm_password">Confirm New Password</label>
      <input type="password" name="confirm_password" required>
      <button type="button" onclick="confirmSave('passwordForm')">Change Password</button>
    </form>
  </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="modal">
  <div class="modal-content">
    <p>Do you want to save the changes?</p>
    <button id="modalYes">Yes</button>
    <button id="modalNo">No</button>
  </div>
</div>

<script>
let currentForm = null;

function confirmSave(formId) {
    currentForm = document.getElementById(formId);
    let modalText = (formId === 'profileForm') 
        ? 'Do you want to save the profile changes?'
        : 'Do you want to change your password?';
    document.querySelector('#confirmModal p').textContent = modalText;
    document.getElementById('confirmModal').style.display = 'block';
}

document.getElementById('modalYes').onclick = function() {
    if (currentForm) currentForm.submit();
    document.getElementById('confirmModal').style.display = 'none';
};

document.getElementById('modalNo').onclick = function() {
    document.getElementById('confirmModal').style.display = 'none';
};
</script>

</body>
</html>
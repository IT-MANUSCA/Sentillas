<?php
session_start();
include 'db.php'; // Must define $pdo

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Count cart items
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if ($row && $row['total_items']) {
        $cart_count = $row['total_items'];
    }
}

// Handle search
$search = "";
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE :search OR brand LIKE :search ORDER BY id DESC");
    $stmt->execute([':search' => "%$search%"]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch unique brand names
$brand_stmt = $pdo->query("SELECT DISTINCT brand FROM products");
$brands = $brand_stmt->fetchAll(PDO::FETCH_COLUMN);

// Check if user is already subscribed to newsletter
$subscribed = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $check = $pdo->prepare("SELECT * FROM newsletter_subscribers WHERE user_id = :user_id");
    $check->execute([':user_id' => $user_id]);
    $subscribed = $check->rowCount() > 0;
}

// ⭐ NEW: Get average rating
$avg_rating = $pdo->query("SELECT ROUND(AVG(rating), 1) AS average FROM website_ratings")->fetchColumn();

// ⭐ NEW: Check if user already rated
$current_rating = null;
$stmt = $pdo->prepare("SELECT rating FROM website_ratings WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
if ($row = $stmt->fetch()) {
    $current_rating = (int)$row['rating'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sentillas Airconditioning</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", sans-serif; }
    body { background-image: url(bg.png); color: #333; line-height: 1.6; }
    header { background: white; padding: 1rem 5%; display: flex; justify-content: space-between; align-items: center; color: #333; }
    header h1 { font-size: 1.8rem; font-weight: 600; margin-right: 210px; }
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
    .section { padding: 2rem 5%; }
    .section h3 { font-size: 2rem; margin-bottom: 1rem; color: white; text-align: center; }
    .search-bar { display: flex; justify-content: flex-start; margin-bottom: 2rem; gap: 10px; }
    .search-bar input { flex: 1; padding: 10px 14px; border: 1px solid #ccc; border-radius: 6px; font-size: 1rem; }
    .search-bar button { background: black; color: white; border: 1px solid transparent; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.3s ease; }
    .search-bar button:hover { background: white; color: black; border: 1px solid black; }
    .products { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 2rem; justify-content: center; }
    .card { background: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0px 6px 15px rgba(0,0,0,0.1); display: flex; flex-direction: column; height: 100%; transition: transform 0.3s, box-shadow 0.3s; }
    .card:hover { transform: translateY(-5px); box-shadow: 0px 8px 20px rgba(0,0,0,0.15); }
    .card img { width: 100%; height: 200px; border-radius: 10px; object-fit: cover; margin-bottom: 1rem; }
    .card h4 { font-size: 1.3rem; margin-bottom: 0.5rem; color: #222; }
    .card p { font-size: 0.95rem; margin: 2px 0; color: #555; text-align: left; }
    .btn { background: black; color: white; padding: 10px 20px; border: 1px solid transparent; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: auto; text-align: center; font-weight: 500; transition: all 0.3s ease; }
    .btn:hover { background: white; color: black; border: 1px solid black; }
    footer { background: #0d1a32; color: #fff; padding: 2rem 16%; margin-top: 2rem; }
    .footer-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2rem; margin-bottom: 1.5rem; }
    .footer-column h3 { margin-bottom: 1rem; font-size: 1.2rem; color: #9a8cff; }
    .footer-column ul { list-style: none; padding: 0; }
    .footer-column ul li { margin-bottom: 0.5rem; }
    .footer-column ul li a { color: #fff; text-decoration: none; transition: 0.3s; }
    .footer-column ul li a:hover { color: #9a8cff; }
    .footer-column p, .footer-column a { font-size: 14px; color: #fff; line-height: 1.6; transition: all 0.3s ease; }
    .footer-column a { background: rgba(255, 255, 255, 0.1); padding: 4px 8px; border-radius: 6px; display: inline-block; text-decoration: none; }
    .footer-column a:hover { background: rgba(154, 140, 255, 0.3); color: #fff; }
    .footer-bottom { text-align: center; border-top: 1px solid rgba(255, 255, 255, 0.2); padding-top: 1rem; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 10px; }
    .footer-logo { width: 100px; height: auto; border-radius: 12px; object-fit: contain; background: white; box-shadow: 0px 4px 12px rgba(0,0,0,0.2); transition: transform 0.3s ease; }
    .footer-logo:hover { transform: scale(1.08); }
    .footer-column form input { padding: 8px; border: none; border-radius: 6px; margin-right: 6px; }
    .footer-column form button { background: black; color: white; border: 1px solid transparent; padding: 8px 14px; border-radius: 6px; cursor: pointer; transition: 0.3s; }
    .footer-column form button:hover { background: white; color: black; border: 1px solid black; }
    .flash-message { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999; background-color: #4CAF50; color: #fff; padding: 15px 25px; border-radius: 6px; font-size: 16px; text-align: center; animation: fadeOut 0.5s ease-in-out 3s forwards; box-shadow: 0px 4px 12px rgba(0,0,0,0.2); }
    @keyframes fadeOut { to { opacity: 0; visibility: hidden; } }
    .maintenance-form { background: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0px 6px 15px rgba(0,0,0,0.1); margin-top: 2rem; }
    .maintenance-form textarea, 
    .maintenance-form input, 
    .maintenance-form select { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 1rem; margin-bottom: 1rem; }
    .star-rating {
      display: inline-flex;
      flex-direction: row-reverse;
      justify-content: flex-end;
      gap: 6px;
      font-size: 24px;
      margin-top: 5px;
    }

    .star-rating input[type="radio"] {
      display: none;
    }

    .star-rating label {
      color: #ccc;
      cursor: pointer;
      transition: color 0.3s ease;
    }

    .star-rating label:hover,
    .star-rating label:hover ~ label,
    .star-rating input[type="radio"]:checked ~ label {
      color: #f5c518;
    }

    .disabled-rating label {
      pointer-events: none;
      opacity: 0.6;
    }

    /* Flash message */
    .flash-message {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 9999;
      background-color: #4CAF50;
      color: #fff;
      padding: 15px 25px;
      border-radius: 6px;
      font-size: 16px;
      text-align: center;
      animation: fadeOut 0.5s ease-in-out 3s forwards;
      box-shadow: 0px 4px 12px rgba(0,0,0,0.2);
    }

    @keyframes fadeOut {
      to {
        opacity: 0;
        visibility: hidden;
      }
    }
    
        /* MODAL BASE */
.modal {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  z-index: 10000;
  align-items: center;
  justify-content: center;
}

 .modal-content {
     background: #fff;
     padding: 2rem;
     border-radius: 12px;
     width: 500px;
     max-width: 90%;
     box-shadow: 0px 10px 30px rgba(0,0,0,0.2);
     animation: fadeIn 0.3s ease;
     text-align: center;
 }

.modal-content h2 {
  margin-bottom: 10px;
}

 .terms-text {
     max-height: 300px;
     overflow-y: auto;
     text-align: justify; 
     font-size: 17px;
     line-height: 1.6;
     margin-bottom: 1.5rem;
 }
 .terms-text {
    font-size: 1.2em;
    text-align: justify;
}

.modal-buttons {
  text-align: center;
  margin-top: 20px;
}

.modal-buttons button {
  background: black;
  color: white;
  border: 1px solid black;
  padding: 8px 18px;
  border-radius: 8px;
  cursor: pointer;
  transition: 0.3s;
}

.modal-buttons button:hover {
  background: white;
  color: black;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

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
  .section {
    padding: 2rem 4%;
  }

  .section h3 {
    font-size: 1.6rem;
    margin-bottom: 1.5rem;
  }

  .search-bar {
    flex-direction: column;
    gap: 10px;
  }

  .search-bar input,
  .search-bar button {
    width: 100%;
  }

  .products {
    grid-template-columns: 1fr;
  }

  .card {
    padding: 1rem;
  }

  .card h4 {
    font-size: 1.1rem;
  }

  .card p {
    font-size: 0.9rem;
  }

  .card img {
    height: auto;
    max-height: 220px;
    border-radius: 10px;
  }

  .btn {
    width: 100%;
    padding: 10px 0;
    font-size: 0.95rem;
  }

  footer {
    padding: 2rem 5%;
  }

  .footer-container {
    grid-template-columns: 1fr;
  }

  .footer-column form input {
    width: 100%;
    margin-bottom: 10px;
  }

  .footer-column form button {
    width: 100%;
  }

  .footer-bottom {
    flex-direction: column;
    gap: 8px;
    text-align: center;
  }

  .footer-logo {
    width: 80px;
  }

  .star-rating {
    justify-content: center;
  }

  .star-rating label {
    font-size: 20px;
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

<!-- Header -->
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

<!-- Product Section -->
<section class="section">
  <h3>Products</h3>
  <form class="search-bar" method="get" action="shop.php">
    <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
  </form>

  <div class="products">
    <?php if (count($products) > 0): ?>
      <?php foreach ($products as $row): ?>
        <div class="card">
          <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
          <h4><?= htmlspecialchars($row['name']) ?></h4>
          <p><strong>Brand:</strong> <?= htmlspecialchars($row['brand']) ?></p>
          <p><strong>Price:</strong> ₱<?= number_format($row['price'], 2) ?></p>
          <a href="product_details.php?id=<?= $row['id'] ?>" class="btn">View Details</a>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No products found for your search.</p>
    <?php endif; ?>
  </div>
</section>

<!-- Footer -->
<footer class="footer">
  <div class="footer-container">
    <!-- Contact Column -->
    <div class="footer-column">
      <h3>Contact Us</h3>
      <p>Sentillas Airconditioning</p>
      <p>178 Kaingin Road, Apolonio Samson Balintawak, Quezon City, Philippines.</p>
      <p>EMAIL</p>
      <p><a href="mailto:sentillasaircon@gmail.com">sentillasaircon@gmail.com</a></p>
      <p>PHONE</p>
      <p><a href="tel:+639382500639">+63 938 250 0639</a></p>
    </div>

    <!-- Quick Links Column -->
<div class="footer-column">
  <h3>Quick Links</h3>
  <ul class="quick-links">
    <li><a href="javascript:void(0)" id="openPrivacy"><i class="fas fa-user-shield"></i> Privacy Policy</a></li>
    <li><a href="javascript:void(0)" id="openTerms"><i class="fas fa-file-contract"></i> Terms of Service</a></li>
    <li><a href="javascript:void(0)" id="openFaq"><i class="fas fa-circle-question"></i> FAQs</a></li>
    <li><a href="javascript:void(0)" id="openServices"><i class="fas fa-screwdriver-wrench"></i> Services</a></li>
  </ul>
</div>


    <!-- Newsletter + RATING Column -->
    <div class="footer-column">
      <h3>Newsletter</h3>
      <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="flash-message <?= $_SESSION['flash_message']['type']; ?>">
          <?= $_SESSION['flash_message']['text']; ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
      <?php endif; ?>

      <?php
      $subscribed = false;
      $stmt = $pdo->prepare("SELECT * FROM newsletter_subscribers WHERE user_id = ?");
      $stmt->execute([$_SESSION['user_id']]);
      if ($stmt->fetch()) {
          $subscribed = true;
      }
      ?>

      <form action="subscribe.php" method="post">
        <input type="email" 
               name="email" 
               placeholder="<?= $subscribed ? '' : 'Enter your email'; ?>" 
               value="<?= $_SESSION['email'] ?? ''; ?>" 
               <?= $subscribed ? 'disabled style="background:#222; cursor:not-allowed;"' : ''; ?> 
               required>  
        <button type="submit" <?= $subscribed ? 'disabled' : ''; ?>>
          <?= $subscribed ? 'Already Subscribed' : 'Subscribe'; ?>
        </button>
      </form>

      <!-- ⭐ Website Rating Form -->
      <h3 style="margin-top: 25px;">Rate Our Website</h3>
      <form action="rate_website.php" method="POST" style="margin-top: 10px;">
        <input type="hidden" name="user_id" value="<?= $_SESSION['user_id']; ?>">

        <div class="star-rating <?= $current_rating ? 'disabled-rating' : '' ?>" style="direction: rtl;">
          <?php
          $labels = [5 => "Excellent", 4 => "Good", 3 => "Average", 2 => "Poor", 1 => "Terrible"];
          for ($i = 5; $i >= 1; $i--):
          ?>
            <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= $current_rating == $i ? 'checked' : '' ?> <?= $current_rating ? 'disabled' : '' ?>>
            <label for="star<?= $i ?>" title="<?= $labels[$i] ?>" class="fa fa-star"></label>
          <?php endfor; ?>
        </div>

        <button type="submit" <?= $current_rating ? 'disabled style="background: #444; cursor: not-allowed;"' : '' ?> style="margin-top: 10px;">
          <?= $current_rating ? 'Rating Submitted' : 'Submit Rating'; ?>
        </button>
      </form>

      <?php if ($current_rating): ?>
        <p style="margin-top: 8px; font-size: 13px; color: #ccc;">
          You rated us: <strong><?= $current_rating ?> / 5</strong>
        </p>
      <?php endif; ?>
    </div>
  </div>

  <div class="footer-bottom">
    <img src="ac.png" alt="Sentillas Airconditioning Logo" class="footer-logo">
    <span>&copy; <?= date('Y'); ?> Sentillas Airconditioning. All rights reserved.</span>
  </div>
</footer>

<!-- Privacy Policy Modal -->
<div id="privacyModal" class="modal">
  <div class="modal-content">
    <h2>Privacy Policy</h2>
    <div class="terms-text">
      <p>
        Your privacy is important to us. Any personal data you provide will only be used 
          for service delivery and communication. We do not share your information with 
          third parties without consent, except as required by law. <br> <br>
          We use industry-standard security measures to safeguard your data, and only authorized
          personnel have access to it. You may request updates or deletion of your data by contacting us directly.
      </p>
    </div>
    <div class="modal-buttons">
      <button class="closeBtn">Close</button>
    </div>
  </div>
</div>

<!-- Terms of Service Modal -->
<div id="termsModal" class="modal">
  <div class="modal-content">
    <h2>Terms of Service</h2>
    <div class="terms-text">
      <p>
        <b> Service Coverage </b> <br>
            All services are provided as described on our website or during
            communication with our team. The scope and scheduling may vary depending on technician
            availability and service demand. <br> <br>
            <b> Payment Terms </b> <br>
            Payments must be settled before or upon service completion unless
            otherwise agreed. <br> <br>
            <b> Accepted Payment Methods </b> <br>
            <b> • Cash Payments:</b> Pay directly to our technician after service completion. <br>
            <b> • Credit Card Payment:</b> Client must visit the store directly. <br>
                — BDO (Installation allowed) + 4% additional 1% every month <br>
                — Other bank (Straight payment) + 4% <br>
            <b> • Bank Transfer:</b> Send your payment to our official business account which will be provided upon confirmation. <br>
            <b> • GCash:</b> Payments can be made via GCash to the number provided after booking confirmation. Please send a screenshot of your payment for verification. <br> <br>
            <b> Service Responsibility </b> <br>
            We ensure quality and care in every service. However, we are not liable for issues caused by customer misuse, neglect, or third-party interference after our work is completed. <br> <br>
            <b> Privacy & Security </b> <br>
            All customer information is kept confidential and used solely for communication and service purposes. <br> <br>
            <b> Updates to Terms </b> <br>
            These terms may be revised or updated without prior notice to improve service quality and transparency.
        </p>
      </p>
    </div>
    <div class="modal-buttons">
      <button class="closeBtn">Close</button>
    </div>
  </div>
</div>

<!-- FAQs Modal -->
<div id="faqModal" class="modal">
  <div class="modal-content">
    <h2>FAQs</h2>
    <div class="terms-text">
      <p>
          <b>Q:</b> How often should I clean my air conditioner?<br>
          <b>A:</b> We recommend cleaning every 3–6 months for optimal cooling and efficiency.<br><br>
          <b>Q:</b> Do you offer emergency repair services?<br>
          <b>A:</b> Yes. We provide emergency repair services depending on technician availability and location.<br><br>
          <b>Q:</b> What brands do you install?<br>
          <b>A:</b> We install all major air conditioning brands available in the Philippines, including Carrier, Panasonic, LG, Samsung, Daikin, and more. <br> <br>
          <b>Q:</b> Do you provide service warranties?<br>
          <b>A:</b> Yes, we offer a limited service warranty depending on the type of service performed. The details will be provided during the transaction. Please note that if any service involving cleaning or repairs is performed on the air-conditioning unit by a third party, the warranty will be void.
        </p>
    </div>
    <div class="modal-buttons">
      <button class="closeBtn">Close</button>
    </div>
  </div>
</div>

<!-- Services Modal -->
<div id="servicesModal" class="modal">
  <div class="modal-content">
    <h2>Services Offered</h2>
    <div class="terms-text">
      <ul>
        <li><b>Installation</b> – Installation of central air systems, ductless mini-splits, heat pumps, and packaged units.</li>
        <li><b>Maintenance</b> – Diagnosis and repair of cooling issues, leaks, electrical problems, cleaning, tune-ups and system breakdowns.</li>
        <li><b>AC Repair</b> – Diagnostics and fixes, seasonal tune-ups, cleaning, and performance checks.</li>
        <li><b>Indoor Air Quality Solutions</b> – Diagnostics and fixes, seasonal tune-ups, cleaning, and performance checks.</li>
      </ul> <br>
      
      <p><strong>Installation Package</strong><br>
            • 10ft Copper Tube <br>
            • 1 length PVC Pipe Tube <br>
            • 10ft Communication Wire <br>
            • Basic Outdoor Bracket <br>
            • Free Circuit Breaker <br>
            • Equipment and Manpower <br>
            </p> <br>
            
            <p><strong>Exclusions</strong><br>
            • 1,200 Circuit Breaker <br>
            • 100ft Power Supply <br>
            • Excess Copper Tube: 350/ft - 450/ft <br>
            • Excess PVC pipe: 150/PC <br>
            • Chipping Works: 75/ft <br>
            </p> <br>
            
            <p> <i> Rest assured that all units are <b> brand new </b> and <b> factory sealed </b> with complete receipt and covered with <b> warranty</b>. </i></p>
    </div>
    <div class="modal-buttons">
      <button class="closeBtn">Close</button>
    </div>
  </div>
</div>
</body>

<script>
    
    function openModal(id) {
    document.getElementById(id).style.display = "flex";
  }

  document.getElementById("openPrivacy").onclick = () => openModal("privacyModal");
  document.getElementById("openTerms").onclick = () => openModal("termsModal");
  document.getElementById("openFaq").onclick = () => openModal("faqModal");
  document.getElementById("openServices").onclick = () => openModal("servicesModal");

  document.querySelectorAll(".closeBtn").forEach(btn => {
    btn.onclick = () => btn.closest(".modal").style.display = "none";
  });

  window.onclick = function (e) {
    document.querySelectorAll(".modal").forEach(modal => {
      if (e.target === modal) modal.style.display = "none";
    });
  };

</script>
</html>
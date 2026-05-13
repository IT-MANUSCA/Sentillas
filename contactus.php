<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if ($row && $row['total_items']) {
        $cart_count = $row['total_items'];
    }
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Contact Us | Sentillas Aircon</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
       <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap">
         <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
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
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .container h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 20px;
            color: white;
            text-align: center;
        }

        .container p {
            font-size: 16px;
            color: #444;
            line-height: 1.7;
        }

        .location-map {
            margin-top: 30px;
        }

        #map {
            width: 100%;
            height: 400px;
            border-radius: 12px;
        }

        .leaflet-popup-content a {
            color: #1a73e8;
            text-decoration: underline;
            font-weight: 600;
        }

    footer {
      background: #0d1a32;
      color: #fff;
      padding: 2rem 28%;
      margin-top: 2rem;
    }

    .footer-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 2rem;
      margin-bottom: 1.5rem;
    }

    .footer-column h3 {
      margin-bottom: 1rem;
      font-size: 1.2rem;
      color: #9a8cff;
    }

    .footer-column ul {
      list-style: none;
      padding: 0;
    }

    .footer-column ul li {
      margin-bottom: 0.5rem;
    }

    .footer-column ul li a {
      color: #fff;
      text-decoration: none;
      transition: 0.3s;
    }

    .footer-column ul li a:hover {
      color: #9a8cff;
    }

    .footer-column p,
    .footer-column a {
      font-size: 14px;
      color: #fff;
      line-height: 1.6;
      transition: all 0.3s ease;
    }

    .footer-column a {
      background: rgba(255, 255, 255, 0.1);
      padding: 4px 8px;
      border-radius: 6px;
      display: inline-block;
      text-decoration: none;
    }

    .footer-column a:hover {
      background: rgba(154, 140, 255, 0.3);
      color: #fff;
    }

.footer-bottom {
    margin-top: 40px;
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  text-align: center;
}

    /* FOOTER LOGO */
.footer-logo { width: 100px; height: auto; border-radius: 12px; object-fit: contain; background: white; box-shadow: 0px 4px 12px rgba(0,0,0,0.2); transition: transform 0.3s ease; }
    .footer-logo:hover { transform: scale(1.08); }

    .footer-column form input {
      padding: 8px;
      border: none;
      border-radius: 6px;
      margin-right: 6px;
    }

    .footer-column form button {
      background: black;
      color: white;
      border: 1px solid transparent;
      padding: 8px 14px;
      border-radius: 6px;
      cursor: pointer;
      transition: 0.3s;
      box-sizing: border-box;
    }

    .footer-column form button:hover {
      background: white;
      color: black;
      border: 1px solid black;
    
        }

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

        .flash-message.success { color: #155724; }
        .flash-message.error { color: #721c24; }

        @keyframes fadeOut {
            to {
                opacity: 0;
                visibility: hidden;
            }
        }
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



    @keyframes fadeOut {
      to {
        opacity: 0;
        visibility: hidden;
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

  .container {
    padding: 0 16px;
  }

  .container h1 {
    font-size: 24px;
  }

  .contact-info p,
  .contact-info span {
    display: block;
    margin-top: 10px;
  }

  .contact-info a {
    display: inline-block;
    margin-top: 5px;
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

  .footer {
    padding: 2rem 5%;
  }

  .footer-bottom {
    flex-direction: column;
    gap: 8px;
    text-align: center;
  }

  .footer-logo {
    width: 80px;
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
    <h1>Contact Us</h1>

    <div class="location-map">
        <div id="map"></div>

        <div class="contact-info" style="margin-top: 20px; font-size: 16px; color: #222;">
            <p><strong>Connect with us:</strong></p>
            <p>
                <a href="https://www.facebook.com/profile.php?id=100064272395012&mibextid=LQQJ4d" target="_blank" rel="noopener noreferrer" style="color:#1877F2; text-decoration:none; margin-right: 15px;">
                    <img src="facebook.png" alt="Facebook" style="width:20px; height:20px; vertical-align: middle; margin-right: 6px;" />
                    Facebook
                </a>
                |
                <span>
                    Contact Number:
                    <img src="phone.png" alt="Phone" style="width:20px; height:20px; vertical-align: middle; margin: 0 1px 0 8px;" />
                    <a href="tel:+639382500639" style="color:#000; text-decoration:none;">0938 250 0639</a>
                </span>
            </p>
            <span>
                Email:
                <img src="gmail.png" alt="Email" style="width:20px; height:20px; vertical-align: middle; margin: 0 5px 0 8px;" />
                <a href="mailto:acspecialist556@gmail.com" style="color:#000; text-decoration:none;">acspecialist556@gmail.com</a>
            </span>
        </div>
    </div>
</div>

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
      </ul><br>
      
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

    
    
    const lat = 14.6579;
    const lng = 121.0052;
    const map = L.map('map').setView([lat, lng], 17);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    const marker = L.marker([lat, lng]).addTo(map);
    marker.bindPopup(`
        <b>Sentillas Airconditioning</b><br>
        178 Kaingin Road, Apolonio Samson Balintawak, Quezon City<br>
        <a href="https://www.google.com/maps/dir/?api=1&destination=178+Kaingin+Road,+Apolonio+Samson+Balintawak,+Quezon+City,+Philippines" 
           target="_blank" 
           rel="noopener noreferrer">
           Get Directions
        </a>
    `);

    setTimeout(() => {
        const flash = document.querySelector('.flash-message');
        if (flash) {
            flash.remove();
        }
    }, 3500);
</script>
</body>
</html>

<?php 
session_start(); 
require 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] === "POST") { 
    $username = htmlspecialchars(trim($_POST['username'])); 
    $password = $_POST['password']; 

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?"); 
    $stmt->execute([$username]); 
    $user = $stmt->fetch(); 

    if ($user) { 
        if (!$user['is_verified']) { 
            echo "<script>alert('Please verify your email first.'); window.location.href='verify_email.php';</script>"; 
            exit; 
        } 
        if (password_verify($password, $user['password'])) { 
            $_SESSION['username'] = $user['username']; 
            $_SESSION['user_id'] = $user['id']; 
            header('Location: home.php'); 
            exit(); 
        } else { 
            echo "<script>alert('Invalid password.'); window.location.href='index.php';</script>"; 
        } 
    } else { 
        echo "<script>alert('User not found. Please register.'); window.location.href='register.php';</script>"; 
    } 
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
 @font-face {
     font-family: melodrame;
     src:url(melodrame.ttf);
 } 
 * {
     margin: 0;
     padding: 0;
     box-sizing: border-box;
     font-family: "Poppins", sans-serif;
 } 
 body {
     min-height: 100vh;
     display: flex;
     flex-direction: column;
 }
 html {
     scroll-behavior: smooth;
 } 
 /* Navigation Bar */
 header {
    background: white;
    width: 100%;
    padding: 1rem 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #333;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.2);
 }
 /* Logo + Title Wrapper */
 .logo-container {
    display: flex;
    align-items: center;
    gap: 10px; /* space between logo and text */
 }
 /* Logo Image */
 .ac {
    width: 50px;
    height: 50px;
    object-fit: contain;
 }
 /* Title */
 header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
 }
 /* Nav Links */
 nav a {
    font-size: 1.3rem;
    color: #333;
    margin: 0 10px;
    text-decoration: none;
    font-weight: 500;
    padding: 6px 10px;
    border-radius: 6px;
    transition: all 0.3s ease;
 }
 nav a:hover {
    background: #e8ebef;
    transform: translateY(-2px);
 } 
 section {
    padding: 60px 20px;
    background-image: url('bg.png');
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
 }
 .login-section {
     display: flex;
     justify-content: flex-start;
     align-items: center;
     min-height: 80vh;
     padding-left: 10%;
 } 
.form {
    background: #fff;
    padding: 3rem 2.5rem;   
    border-radius: 20px;
    width: 450px;           
    box-shadow: 0px 12px 35px rgba(0,0,0,0.18);
    margin-right: 100px;
    margin-top: 50px;
}
 .form .title {
     font-size: 30px;
     font-weight: 600;
     margin-bottom: 0.4rem;
     text-align: center;
     color: #222;
 }
 .form .message {
     text-align: center;
     font-size: 18px;
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
    padding: 18px 20px;
    border: 1.5px solid #ddd;
    border-radius: 12px;
    outline: none;
    font-size: 20px;
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
    top: 16px;
    left: 18px;
    color: #888;
    font-size: 16px;
    pointer-events: none;
    transition: 0.2s;
}

.input:focus + span, .input:not(:placeholder-shown) + span {
    top: -10px;
    left: 12px;
    background: #fff;
    font-size: 13px;
    color: #6c63ff;
    padding: 0 4px;
}
.submit {
    width: 100%;
    padding: 18px;
    background: #1a3363;
    border: none;
    border-radius: 12px;
    font-size: 19px;
    font-weight: 600;
    color: #fff;
    cursor: pointer;
    transition: all 0.25s ease;
}

 .submit:hover {
     transform: translateY(-2px);
     box-shadow: 0px 6px 15px rgba(108, 99, 255, 0.3);
 } 
.signin {
    text-align: center;
    margin-top: 1.2rem;
    font-size: 16px;
}
 .signin a {
     color: #6c63ff;
     text-decoration: none;
     font-weight: 500;
 } 
 .signin a:hover {
     text-decoration: underline;
 } 
 footer {
     background: #0d1a32;
     color: #fff;
     padding: 2rem 16%;
     margin-top: -300px;
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
     color: white;
     text-decoration: none;
     transition: 0.3s;
     cursor: pointer;
 } 
 .footer-column ul li a:hover {
     color: #1a3363;
 } 
 .footer-column p, .footer-column a {
     font-size: 14px;
     color: white;
     line-height: 1.6;
     transition: all 0.3s ease;
 } 
 .footer-column a {
     background: #1a3363;
     padding: 4px 8px;
     border-radius: 6px;
     display: inline-block;
     text-decoration: none;
 } 
 .footer-column a:hover {
     background-color: #e8ebef;
     color: white;
 } 
.footer-bottom {
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #555;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.footer-logo {
    width: 90px;
    height: auto;
    background-color: white;
    border-radius: 50px;
}
 .footer-logo:hover {
     transform: scale(1.08);
 } 
 #tagline {
     font-size: 6rem;
     font-weight: 550;
     font-family: melodrame;
     margin-left: 90px;
     margin-top: 150px;
 } 
 #tagline2 {
     font-size: 6rem;
     font-weight: 550;
     font-family: melodrame;
     margin-left: 90px;
     margin-top: -50px;
 } 
 #desc1 {
     font-size: 1.5rem;
     font-weight: 300;
     margin-left: 90px;
     margin-top: 3px;
 } 
 #home, #services {
    scroll-margin-top: 100px;
}
 /* Modal Background */
 .modal {
     display: none;
     position:fixed;
     z-index: 2000;
     left: 0;
     top: 0;
     width: 100%;
     height: 100%;
     background: rgba(0,0,0,0.6);
     align-items: center; justify-content: center;
 } 
 /* Modal Box */
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
     margin-bottom: 1rem;
     font-size: 1.5rem;
     font-weight: 600; 

 }
/* Scrollable Text */
 .terms-text {
     max-height: 300px;
     overflow-y: auto;
     text-align: justify; 
     font-size: 17px;
     line-height: 1.6;
     margin-bottom: 1.5rem;
 }
 /* Buttons Row */
 .modal-buttons {
     display: flex;
     justify-content: center;
     gap: 10px;
     
 }
 .modal-buttons button {
     padding: 10px 20px;
     border: none;
     border-radius: 8px;
     background: #000;
     color: #fff;
     font-size: 14px;
     cursor: pointer;
     transition: 0.3s;
 }
 .modal-buttons button:hover {
     background: #333;
 }
 @keyframes fadeIn {
     from {
         opacity: 0;
         transform: translateY(15px);
     } to {
         opacity: 1;
         transform: translateY(0);}
 }
 /* Home layout */
.home-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 60px 5%;
    padding-bottom: 450px;
    background-image: url('bg.png');
    background-size: cover;
    background-position: center;
    gap: 40px;
}
.home-text {
    max-width: 55%;
}
.login-wrapper {
    display: flex;
    justify-content: flex-start;
}
.terms-text {
    font-size: 1.2em;
    text-align: justify;
}
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <div class="logo-container">
        <img src="ac.png" alt="Logo" class="ac">
        <h1>Sentillas Airconditioning</h1>
    </div>
    <nav>
        <a href="javascript:void(0)" onclick="document.getElementById('servicesMainModal').style.display='flex'">Services</a>
    </nav>
</header>
<!-- NAVBAR SERVICES DESCRIPTION MODAL -->
<div id="servicesMainModal" class="modal">
    <div class="modal-content">
        <h2>Our Services</h2>
        <div class="terms-text">
            <p>
                We provide reliable, high-quality air-conditioning solutions designed to keep your home or business comfortable year-round. 
                Our certified technicians deliver expert installation, maintenance, and repair services for all major AC brands and systems.
            </p>

            <p>
                <br> Whether you're looking to improve energy efficiency, upgrade outdated equipment, or restore cooling during peak temperatures, 
                we offer fast, friendly, and dependable service you can trust. <br> <br>
            </p>

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
  <!-- Home Section -->
<section id="home" class="home-container">
    <div class="home-text">
        <p id="tagline">Breathe easy,</p>
        <p id="tagline2">where quality meets comfort.</p>

        <p id="desc1">
            Delivering clean, reliable, and energy-efficient cooling to create <br>
            healthier, more comfortable spaces. Our air conditioning services are <br>
            designed to keep your space not only cool but also healthy and <br> energy-efficient.
            We prioritize quality in every step, from equipment to <br> service, ensuring you enjoy
            cleaner air, lower energy costs, and a more <br> comfortable living or working environment.
        </p>
    </div>
    <!-- Login Form BESIDE the text -->
    <div class="login-wrapper">
        <form class="form" method="POST" action="index.php">
            <p class="title">Login</p>
            <p class="message">Login to access your account.</p>

            <label>
                <input required name="username" type="text" class="input" placeholder=" ">
                <span>Username</span>
            </label>

            <label>
                <input required name="password" type="password" class="input" placeholder=" ">
                <span>Password</span>
            </label>

            <button class="submit" type="submit">Login</button>

            <p class="signin">Forgot Password? <a href="forgot_password.php">Click here</a></p>
            <p class="signin">Don't have an account? <a href="register.php">Register</a></p>
        </form>
    </div>
</section>
  <!-- Footer -->
  <footer class="footer">
    <div class="footer-container">
      <div class="footer-column">
        <h3>Contact Us</h3>
        <p>Sentillas Airconditioning</p>
        <p>178 Kaingin Road, Apolonio Samson Balintawak, Quezon City, Philippines.</p>
        <p>EMAIL</p>
        <p><a href="mailto:sentillasaircon@gmail.com">sentillasaircon@gmail.com</a></p>
        <p>PHONE</p>
        <p><a href="tel:+639382500639">+63 938 250 0639</a></p>
      </div>

<div class="footer-column">
  <h3>Quick Links</h3>
  <ul class="quick-links">
    <li><a href="javascript:void(0)" id="openPrivacy"><i class="fas fa-user-shield"></i> Privacy Policy</a></li>
    <li><a href="javascript:void(0)" id="openTerms"><i class="fas fa-file-contract"></i> Terms of Service</a></li>
    <li><a href="javascript:void(0)" id="openFaq"><i class="fas fa-circle-question"></i> FAQs</a></li>
    <li><a href="javascript:void(0)" id="openServices"><i class="fas fa-screwdriver-wrench"></i> Services</a></li>
  </ul>
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
      </ul>
    </div>
    <div class="modal-buttons">
      <button class="closeBtn">Close</button>
    </div>
  </div>
</div>

  <!-- Scripts -->
  <script>
    // Modal logic for all modals
    const modals = {
      privacy: document.getElementById("privacyModal"),
      terms: document.getElementById("termsModal"),
      faq: document.getElementById("faqModal"),
      services: document.getElementById("servicesModal"),
    };

    document.getElementById("openPrivacy").onclick = () => modals.privacy.style.display = "flex";
    document.getElementById("openTerms").onclick = () => modals.terms.style.display = "flex";
    document.getElementById("openFaq").onclick = () => modals.faq.style.display = "flex";
    document.getElementById("openServices").onclick = () => modals.services.style.display = "flex";

    document.querySelectorAll(".closeBtn").forEach(btn => {
      btn.onclick = () => Object.values(modals).forEach(m => m.style.display = "none");
    });

    window.onclick = function(e) {
      Object.values(modals).forEach(m => {
        if (e.target === m) m.style.display = "none";
      });
    };
    
    modals.servicesMain = document.getElementById("servicesMainModal");

  </script>
</body>
</html>
<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';

// Get user province
$user_stmt = $pdo->prepare("SELECT province FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_row = $user_stmt->fetch();
$user_province = $user_row['province'] ?? '';

// Get cart count
$cart_stmt = $pdo->prepare("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id = ?");
$cart_stmt->execute([$user_id]);
$row = $cart_stmt->fetch();
$cart_count = $row && $row['total_items'] ? $row['total_items'] : 0;

// Get orders with warranty
$stmt = $pdo->prepare("
    SELECT p.*, pd.warranty 
    FROM payments p
    LEFT JOIN products prod ON p.product_name = prod.name
    LEFT JOIN product_descriptions pd ON prod.id = pd.product_id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

// New delivery calculation based on paid date
function estDelivery($province, $date_paid) {
    $metro = [
        'Quezon City', 'Manila', 'Makati', 'Pasig', 'Taguig',
        'Caloocan', 'Marikina', 'Mandaluyong', 'Parañaque', 'Pasay',
        'San Juan', 'Valenzuela', 'Las Piñas', 'Muntinlupa',
        'Rizal', 'Bulacan', 'Cavite'
    ];

    $two_day = ['Laguna', 'Batangas', 'Nueva Ecija', 'Pampanga'];

    $start = new DateTime($date_paid);

    if (in_array($province, $metro)) {
        $start->modify('+1 day');
        return $start->format('Y-m-d');
    } elseif (in_array($province, $two_day)) {
        $d1 = clone $start;
        $d1->modify('+1 day');
        $d2 = clone $start;
        $d2->modify('+2 days');
        return $d1->format('Y-m-d') . ' to ' . $d2->format('Y-m-d');
    } else {
        $d1 = clone $start;
        $d1->modify('+1 day');
        $d5 = clone $start;
        $d5->modify('+5 days');
        return $d1->format('Y-m-d') . ' to ' . $d5->format('Y-m-d');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Orders - Sentillas Airconditioning</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
      margin-right: 226px;
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
    .orders-wrapper {
        margin: 2rem auto;
        width: 90%;
        max-width: 1200px;
        background: #fff;
        padding: 30px;
        border-radius: 14px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    }
    .orders-wrapper h1 {
        font-size: 2rem;
        color: #111827;
        font-weight: 700;
        margin-bottom: 20px;
        text-align: center;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    th, td {
        padding: 14px 12px;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
        font-size: 0.95rem;
    }
    th {
        background-color: #f3f4f6;
        color: #111827;
    }
    tr:hover {
        background-color: #f9fafb;
    }
    .btn {
        background: black;
        color: white;
        padding: 8px 14px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .btn:hover {
        background: white;
        color: black;
        border: 1px solid black;
    }
    .empty-orders {
        text-align: center;
        font-size: 1.1rem;
        padding: 40px 10px;
        color: #6b7280;
    }
    .delivering {
        color: green;
        font-weight: 600;
    }
    .modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.6);
        justify-content: center;
        align-items: center;
    }
    .modal.active {
        display: flex;
    }
    .modal-content {
        background: white;
        padding: 20px;
        border-radius: 12px;
        width: 90%;
        max-width: 600px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        position: relative;
    }
    .modal-content img,
    .modal-content iframe {
        width: 100%;
        max-height: 500px;
        border-radius: 10px;
        object-fit: contain;
    }
    .close-btn {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 18px;
        background: none;
        border: none;
        cursor: pointer;
        font-weight: bold;
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

  .orders-wrapper {
    padding: 20px 16px;
  }

  .orders-wrapper h1 {
    font-size: 1.5rem;
  }

  table {
    display: none; /* Hide table on mobile */
  }

  /* Responsive Order Cards */
  .order-card {
    display: block;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 16px;
    font-size: 0.95rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  }

  .order-card strong {
    display: block;
    margin-bottom: 4px;
    color: #111827;
  }

  .order-card .btn {
    margin-top: 10px;
    display: inline-block;
  }

  .empty-orders {
    font-size: 1rem;
    padding: 30px 10px;
  }

  .modal-content {
    width: 95%;
    padding: 16px;
  }

  .modal-content iframe {
    height: 300px;
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
  <div class="page-container">
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

    <div class="orders-wrapper">
      <h1>My Orders</h1>

      <?php if (count($orders) > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Product</th>
              <th>Quantity</th>
              <th>Amount</th>
              <th>Payment Method</th>
              <th>Date Paid</th>
              <th>Est. Delivery</th>
              <th>Warranty</th>
              <th>Proof</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $order):
              $date_paid = date('Y-m-d', strtotime($order['created_at']));
              $est_delivery = estDelivery($user_province, $date_paid);
            ?>
            <tr>
              <td><?= htmlspecialchars($order['product_name']) ?></td>
              <td><?= htmlspecialchars($order['quantity']) ?></td>
              <td>₱<?= number_format($order['total_amount'], 2) ?></td>
              <td><?= strtoupper(htmlspecialchars($order['payment_method'])) ?></td>
              <td><?= $date_paid ?></td>
              <td><?= $est_delivery ?></td>
              <td><?= !empty($order['warranty']) ? htmlspecialchars($order['warranty']) : 'No warranty' ?></td>
              <td>
                <?php if(!empty($order['proof_of_payment'])): ?>
                  <button class="btn" onclick="viewProof('<?= htmlspecialchars($order['proof_of_payment'], ENT_QUOTES) ?>')">View</button>
                <?php else: ?>
                  No proof
                <?php endif; ?>
              </td>
              <td>
    <button class="btn" style="background:#b91c1c;" onclick="removeRow(this)">Remove</button>
</td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-orders">You don’t have any verified orders yet.</div>
      <?php endif; ?>
    </div>

    <div class="modal" id="proofModal">
      <div class="modal-content">
        <button class="close-btn" onclick="closeModal()">✖</button>
        <div id="proofContainer"></div>
      </div>
    </div>
  </div>

  <script>
    function viewProof(url) {
      const modal = document.getElementById('proofModal');
      const container = document.getElementById('proofContainer');
      container.innerHTML = '';

      const safeUrl = decodeURIComponent(url);
      if (safeUrl.endsWith('.pdf')) {
        const iframe = document.createElement('iframe');
        iframe.src = safeUrl;
        iframe.style.height = "500px";
        iframe.setAttribute("frameborder", "0");
        container.appendChild(iframe);
      } else {
        const img = document.createElement('img');
        img.src = safeUrl;
        container.appendChild(img);
      }

      modal.classList.add('active');
    }

    function closeModal() {
      document.getElementById('proofModal').classList.remove('active');
    }
    
    function removeRow(button) {
    const row = button.closest('tr');
    row.style.display = "none"; // hides the row only
}
  </script>
</body>
</html>

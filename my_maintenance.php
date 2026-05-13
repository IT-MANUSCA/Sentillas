<?php
session_start();
require 'db.php';
$pdo->exec("SET time_zone = '+08:00'"); // Fix MySQL timezone

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Count cart items
$cart_count = 0;
$stmt = $pdo->prepare("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$row = $stmt->fetch();
if ($row && $row['total_items']) {
    $cart_count = $row['total_items'];
}

// Fetch user address
$user_stmt = $pdo->prepare("SELECT city, province, address, cellphone_number FROM users WHERE id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_request']) && isset($_POST['delete_request_id'])) {
    $request_id = intval($_POST['delete_request_id']);
    $stmt = $pdo->prepare("DELETE FROM maintenance_requests WHERE id = ? AND user_id = ?");
    $stmt->execute([$request_id, $_SESSION['user_id']]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Maintenance request canceled successfully.'];
    header('Location: my_maintenance.php');
    exit();
}

// Handle user confirming maintenance request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_request_id'])) {
    $request_id = intval($_POST['confirm_request_id']);
    $stmt = $pdo->prepare("UPDATE maintenance_requests SET user_confirmed = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$request_id, $_SESSION['user_id']]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'You have confirmed this maintenance request.'];
    header('Location: my_maintenance.php');
    exit();
}

// Submit maintenance request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $product_name = trim($_POST['product_name']);
    $brand_name = trim($_POST['brand_name']);
    $warranty_code = trim($_POST['warranty_code'] ?? '');
    $issues = isset($_POST['issues']) ? $_POST['issues'] : [];
    $custom_issue = trim($_POST['custom_issue']);
    $issue = implode(", ", $issues);
    if (!empty($custom_issue)) {
        $issue .= "\nAdditional details: " . $custom_issue;
    }

    $image_path = null;
    $video_path = null;
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/images/";
        $image_path = $target_dir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $image_path);
    }
    if (!empty($_FILES['video']['name'])) {
        $target_dir = "uploads/videos/";
        $video_path = $target_dir . basename($_FILES["video"]["name"]);
        move_uploaded_file($_FILES["video"]["tmp_name"], $video_path);
    }

    if ($product_name && $brand_name && $issue) {
        $stmt = $pdo->prepare("INSERT INTO maintenance_requests (user_id, product_name, brand_name, issue, image_path, video_path, warranty_code, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $_SESSION['user_id'],
            $product_name,
            $brand_name,
            $issue,
            $image_path,
            $video_path,
            $warranty_code
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Maintenance request submitted!'];
        header('Location: my_maintenance.php');
        exit();
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Please complete all fields.'];
        header('Location: my_maintenance.php');
        exit();
    }
}

// Fetch user's maintenance requests
$request_stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE user_id = ? ORDER BY created_at DESC");
$request_stmt->execute([$_SESSION['user_id']]);
$my_requests = $request_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Maintenance - Sentillas Airconditioning</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="my_maintenance.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:"Poppins", sans-serif; }
body { background-image:url(bg.png); color:#333; line-height:1.6; }
header { background:white; padding:1rem 5%; display:flex; justify-content:space-between; align-items:center; color:#333; }
header h1 { font-size:1.8rem; font-weight:600; margin-right:210px; }
nav a { color:#333; margin:0 10px; text-decoration:none; font-weight:500; padding:6px 10px; border-radius:6px; transition:all 0.3s ease; }
nav a:hover { background:rgba(255,255,255,0.2); transform:translateY(-2px); }
.modal { display:none; position:fixed; z-index:10000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; }
.modal.active { display:flex; }
.modal-content { background:#fff; padding:30px; border-radius:14px; text-align:center; max-width:400px; width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.15); }
.modal-content h2 { margin-bottom:10px; color:#111827; font-size:1.2rem; }
.modal-content p { font-size:0.95rem; color:#6b7280; }
.modal-buttons { display:flex; justify-content:center; gap:20px; margin-top:20px; }
.modal-buttons button { background:black; color:white; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:600; border:1px solid transparent; cursor:pointer; transition:all 0.3s ease; }
.modal-buttons button:hover { background:white; color:black; border:1px solid black; }
.confirm-btn {
    background: black;
    color: white;
    border: 1px solid transparent;
    padding: 4px 8px; /* smaller padding */
    border-radius: 4px; /* smaller radius */
    font-weight: 600;
    font-size: 0.8rem; /* smaller text */
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-block;
    white-space: nowrap;
}

.confirm-btn:hover {
    background: white;
    color: black;
    border: 1px solid black;
}

.delete-btn {
    background: white;
    color: black;
    border: 1px solid black;
    padding: 4px 8px; /* smaller padding */
    border-radius: 4px;
    font-weight: 600;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-block;
    white-space: nowrap;
}

.delete-btn:hover {
    background: black;
    color: white;
}

.confirmed-text {
    color: green;
    font-weight: bold;
    font-size: 0.8rem;
}
@media (max-width:768px) { .modal-content { padding:20px; width:90%; } .modal-buttons { flex-direction:column; gap:10px; } .modal-buttons button { width:100%; } }
.logo-container { display:flex; align-items:center; gap:10px; }
.ac { width:50px; height:50px; object-fit:contain; }
</style>
</head>
<body>
<header>
<div class="logo-container"><img src="ac.png" alt="Logo" class="ac"></div>
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
<?php unset($_SESSION['flash']); endif; ?>

<div class="address-reminder">
<strong>Your Saved Address:</strong><br>
<?= htmlspecialchars($user['city'] ?? '-') ?>, <?= htmlspecialchars($user['province'] ?? '-') ?><br>
<em>(Full Address on file: <?= htmlspecialchars($user['address'] ?? '-') ?>)</em><br>
<strong>Cellphone:</strong> <?= htmlspecialchars($user['cellphone_number'] ?? '-') ?>

<!-- Precise reminders -->
<ul style="margin-top:10px; padding-left:18px; font-size:0.95rem; line-height:1.4;">
    <li><strong>Address Reminder:</strong> Make sure your saved address is correct before submitting a maintenance request.</li>
    <li><strong>Cellphone Reminder:</strong> Ensure your saved cellphone number is up-to-date so the technician can reach you.</li>
</ul>
</div>

<h2>Submit Maintenance Request</h2>
<form method="post" enctype="multipart/form-data">
<div class="form-left">
<label for="product_name">Product Name</label>
<input type="text" name="product_name" required>
<label for="brand_name">Brand Name</label>
<input type="text" name="brand_name" required>
<label>Select Common Issue(s)</label>
<div class="checkbox-group">
<label><input type="checkbox" name="issues[]" value="Refrigerant leak"> Refrigerant leak</label>
<label><input type="checkbox" name="issues[]" value="Faulty capacitor"> Faulty capacitor</label>
<label><input type="checkbox" name="issues[]" value="Compressor failure"> Compressor failure</label>
<label><input type="checkbox" name="issues[]" value="Fan motor problem"> Fan motor problem</label>
<label><input type="checkbox" name="issues[]" value="Clogged or dirty air filter"> Clogged/Dirty air filter</label>
<label><input type="checkbox" name="issues[]" value="Dirty or damaged coils"> Dirty/Damaged coils</label>
<label><input type="checkbox" name="issues[]" value="Thermostat malfunction"> Thermostat malfunction</label>
<label><input type="checkbox" name="issues[]" value="Drainage issue"> Drainage issue</label>
<label><input type="checkbox" name="issues[]" value="Contactor failure"> Contactor/Relay failure</label>
<label><input type="checkbox" name="issues[]" value="Worn-out belts or bearings"> Worn-out belts/bearings</label>
</div>
</div>

<div class="form-right">
<label for="custom_issue">Additional Details (Optional)</label>
<textarea name="custom_issue" rows="6" placeholder="Describe what's happening"></textarea>
<label for="image">Attach Image (optional)</label>
<input type="file" name="image" accept="image/*">
<label for="video">Attach Video (optional)</label>
<input type="file" name="video" accept="video/*">
<label for="warranty_code">Claimable Warranty Code (if you have one)</label>
<input type="text" name="warranty_code" placeholder="Optional">
<button type="submit" name="submit_request">Submit Request</button>
</div>
</form>

<h2>My Maintenance Requests</h2>

<div class="address-reminder">
<!-- Reminder for confirming maintenance -->
<strong>Reminder:</strong> Please confirm your maintenance request once the admin has confirmed it to ensure proper scheduling.
</div>


<?php if (count($my_requests) > 0): ?>
<table>
<tr>
<th>Product</th>
<th>Brand</th>
<th>Issue</th>
<th>Status</th>
<th>Needed Parts</th>
<th>Estimated Cost</th>
<th>Maintenance Date</th>
<th>Requested At</th>
<th>Action</th>
</tr>
<?php foreach ($my_requests as $req): ?>
<tr id="request-row-<?= $req['id'] ?>">
<td><?= htmlspecialchars($req['product_name']) ?></td>
<td><?= htmlspecialchars($req['brand_name']) ?></td>
<td><?= nl2br(htmlspecialchars($req['issue'])) ?></td>
<td class="status-<?= $req['status'] ?>"><?= $req['status'] ?></td>
<td><?= nl2br(htmlspecialchars($req['admin_remarks'])) ?></td>
<td>₱<?= number_format($req['estimated_cost'], 2) ?></td>
<td>
<?php if (!empty($req['maintenance_date'])): ?>
<?= date('F j, Y g:i A', strtotime($req['maintenance_date'])) ?>
<?php else: ?>
<em>Not yet scheduled</em>
<?php endif; ?>
</td>
<td><?= date('F j, Y g:i A', strtotime($req['created_at'])) ?></td>

<td style="text-align:center;">
<?php if (strtolower($req['status']) === 'confirmed' && !$req['user_confirmed']): ?>
<form method="post" style="display:inline;">
    <input type="hidden" name="confirm_request_id" value="<?= $req['id'] ?>">
    <button type="submit" class="confirm-btn">
        Confirm Maintenance
    </button>
</form>
<?php elseif ($req['user_confirmed']): ?>
<span class="confirmed-text">Confirmed</span>
<?php else: ?>
<button type="button" class="delete-btn" onclick="hideRequestRow(<?= $req['id'] ?>)">Cancel Request</button>
<?php endif; ?>
</td>

</td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p>You haven't submitted any maintenance requests yet.</p>
<?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
<div class="modal-content">
<h2>Are you sure you want to cancel this maintenance request?</h2>
<p>This action cannot be undone.</p>
<form method="post">
<input type="hidden" name="delete_request_id" id="deleteRequestId">
<div class="modal-buttons">
<button type="button" onclick="closeDeleteModal()">No, Keep It</button>
<button type="submit" name="delete_request">Yes, Cancel</button>
</div>
</form>
</div>
</div>

<script>
    
function openDeleteModal(requestId) {
    document.getElementById('deleteRequestId').value = requestId;
    document.getElementById('deleteModal').classList.add('active');
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}
function hideRequestRow(requestId) {
    const row = document.getElementById('request-row-' + requestId);
    if (row) { row.style.display = 'none'; }
}
window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target === modal) { closeDeleteModal(); }
}
</script>
</body>
</html>

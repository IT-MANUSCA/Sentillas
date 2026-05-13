<?php
session_name("admin_session");
session_start();
require 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

// Redirect if not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

// Fetch admin role if not stored
if (!isset($_SESSION['admin_role'])) {
    $stmt = $pdo->prepare("SELECT role FROM admin_users WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $_SESSION['admin_role'] = $stmt->fetchColumn();
}

$admin_id = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT username, role FROM admin_users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_username = $admin['username'] ?? 'Unknown';
$admin_role = $admin['role'] ?? 'admin';

$current_page = basename($_SERVER['PHP_SELF']);

// Restrict POST actions — ONLY admin can perform actions
if ($_SERVER["REQUEST_METHOD"] === "POST" && $admin_role !== 'admin') {
    $_SESSION['flash'] = ['message' => "You do not have permission.", 'type' => 'error'];
    header("Location: maintenance_request.php");
    exit();
}

// Confirm with parts and cost
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_parts'])) {
    $request_id = intval($_POST['request_id']);
    $parts_needed = trim($_POST['admin_remarks'] ?? '');
    $estimated_cost = floatval($_POST['estimated_cost']);
    $maintenance_date = $_POST['maintenance_date'] ?? null;

    $stmt = $pdo->prepare("UPDATE maintenance_requests SET status='Confirmed', admin_remarks=?, estimated_cost=?, maintenance_date=? WHERE id=?");
    $stmt->execute([$parts_needed, $estimated_cost, $maintenance_date, $request_id]);

    // Fetch user info
    $stmtUser = $pdo->prepare("SELECT u.firstname, u.lastname, u.email, mr.issue, mr.admin_remarks, mr.estimated_cost, mr.maintenance_date FROM maintenance_requests mr JOIN users u ON mr.user_id = u.id WHERE mr.id = ?");
    $stmtUser->execute([$request_id]);
    $user = $stmtUser->fetch();

    if ($user) {
        $fullname = $user['firstname'] . ' ' . $user['lastname'];
        $to = $user['email'];
        $issue = $user['issue'];
        $maintenance = $user['maintenance_date'] ? date("M d, Y h:i A", strtotime($user['maintenance_date'])) : 'Not Scheduled';
        $remarks = $user['admin_remarks'];
        $cost = number_format($user['estimated_cost'], 2);

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'aquinojenesis1@gmail.com';
            $mail->Password = 'xrpg gcde xgwk ccan';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('aquinojenesis1@gmail.com', 'Sentillas Air-Conditioning');
            $mail->addAddress($to, $fullname);
            $mail->isHTML(true);
            $mail->Subject = "Maintenance Request Confirmation";
            $mail->Body = "<p>Hi <b>$fullname</b>,</p><p>Your maintenance request regarding <b>$issue</b> has been <b>Confirmed</b>.</p><p><b>Maintenance Schedule:</b> $maintenance</p><p><b>Needed:</b><br>".nl2br(htmlspecialchars($remarks))."</p><p><b>Estimated Cost:</b> ₱$cost</p><p>Regards,<br>Sentillas Air-Conditioning</p>";
            $mail->send();
        } catch (Exception $e) {
            $_SESSION['flash'] = ['message' => "Request updated but email failed: {$mail->ErrorInfo}", 'type' => 'error'];
            header("Location: maintenance_request.php");
            exit();
        }
    }

    $_SESSION['flash'] = ['message' => "Request confirmed and user notified!", 'type' => 'success'];
    header("Location: maintenance_request.php");
    exit();
}

// Decline request
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'Declined') {
    $request_id = intval($_POST['request_id']);
    $stmt = $pdo->prepare("UPDATE maintenance_requests SET status='Declined' WHERE id=?");
    $stmt->execute([$request_id]);
    $_SESSION['flash'] = ['message' => "Request declined.", 'type' => 'success'];
    header("Location: maintenance_request.php");
    exit();
}

// Fetch all requests
$stmt = $pdo->prepare("SELECT mr.*, u.firstname, u.lastname, u.email, u.city, u.province, u.address, u.cellphone_number FROM maintenance_requests mr JOIN users u ON mr.user_id = u.id ORDER BY mr.created_at DESC");
$stmt->execute();
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Maintenance Requests</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');
*{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif}
body{display:flex;height:100vh;background:linear-gradient(135deg,#eef2ff,#f9fafb);color:#111827;overflow:hidden}
.sidebar{width:250px;background:rgba(255,255,255,0.25);backdrop-filter:blur(20px);border-right:1px solid rgba(255,255,255,0.3);box-shadow:6px 0 25px rgba(0,0,0,0.05);color:#111827;padding:30px 20px;display:flex;flex-direction:column;transition:0.3s ease}
.sidebar h2{font-size:1.5rem;margin-bottom:40px;text-align:center;font-weight:600;color:#111827}
.admin-info{text-align:center;margin-bottom:25px;padding:16px;background:rgba(255,255,255,0.5);border-radius:14px;box-shadow:inset 0 1px 3px rgba(0,0,0,0.1)}
.admin-info p{margin:5px 0}
.sidebar hr{border:none;border-top:1px solid rgba(0,0,0,0.1);margin:20px 0}
.sidebar a{text-decoration:none;color:#374151;padding:12px 16px;margin:6px 0;border-radius:10px;display:flex;align-items:center;gap:12px;font-size:1rem;font-weight:500;transition:all 0.25s ease}
.sidebar a:hover{background:rgba(59,130,246,0.1);color:#2563eb;transform:translateX(4px)}
.sidebar a.active{background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;box-shadow:0 4px 12px rgba(37,99,235,0.3)}
.main{flex:1;padding:40px;overflow-y:auto}
h1{font-size:1.8rem;margin-bottom:20px;display:flex;align-items:center;gap:10px}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,0.06)}
th,td{padding:14px 20px;text-align:left}
th{background:#f9fafb}.btn{padding:6px 14px;border:none;border-radius:6px;cursor:pointer}.btn-confirm{background:#2563eb;color:white}.btn-confirm:hover{background:#1d4ed8}.btn-decline{background:#e5e7eb;color:black}.modal{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:none;align-items:center;justify-content:center}.modal-content{background:#fff;padding:20px;border-radius:12px;width:90%;max-width:500px}.close-btn{float:right;cursor:pointer;font-size:20px}input,textarea{width:100%;padding:10px;margin:8px 0;border-radius:8px;border:1px solid #ddd}.btn-info{background:#2563eb;color:white;padding:6px 14px;text-decoration:none;border-radius:6px}.btn-info:hover{background-color:#1d4ed8}
</style>
<script>
function openModal(id){document.getElementById('modal-'+id).style.display='flex'}
function closeModal(id){document.getElementById('modal-'+id).style.display='none'}
</script>
</head>
<body>
<div class="sidebar">
<h2>Sentillas Airconditioning</h2>
<div class="admin-info">
<p><?= htmlspecialchars($admin_username) ?></p>
<small>Role: <?= ucfirst(htmlspecialchars($admin_role)) ?></small>
</div>
<hr>
<a href="dashboard.php" class="<?= $current_page=='dashboard.php'?'active':'' ?>"><i class="fas fa-home"></i> Dashboard</a>
<a href="products.php" class="<?= $current_page=='products.php'?'active':'' ?>"><i class="fas fa-box"></i> Products</a>
<a href="orders.php" class="<?= $current_page=='orders.php'?'active':'' ?>"><i class="fas fa-shopping-cart"></i> Orders</a>
<a href="customers.php" class="<?= $current_page=='customers.php'?'active':'' ?>"><i class="fas fa-users"></i> Customers</a>
<a href="admin_newsletter.php" class="<?= $current_page=='admin_newsletter.php'?'active':'' ?>"><i class="fas fa-envelope"></i> Newsletter</a>
<a href="maintenance_request.php" class="<?= $current_page=='maintenance_request.php'?'active':'' ?>"><i class="fas fa-tools"></i> Maintenance</a>
<a href="warranty_codes.php" class="<?= $current_page=='warranty_codes.php'?'active':'' ?>"><i class="fas fa-shield-alt"></i> Warranty</a>
<?php if ($admin_role === 'admin'): ?><a href="add_admin.php" class="<?= $current_page=='add_admin.php'?'active':'' ?>"><i class="fas fa-user-cog"></i> Add User</a><?php endif; ?>
</div>
<div class="main">
<h1><i class="fas fa-tools"></i> Maintenance Requests</h1>
<?php if(isset($_SESSION['flash'])): $flash=$_SESSION['flash']; ?>
<div id="flash-message" class="flash <?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
<?php unset($_SESSION['flash']); endif; ?>
<table>
<thead><tr><th>User</th><th>Address</th><th>City</th><th>Province</th><th>Cellphone No.</th><th>Status</th><th>Action</th><th></th></tr></thead>
<tbody>
<?php foreach($requests as $req): ?>
<tr>
<td><?= htmlspecialchars($req['firstname'].' '.$req['lastname']) ?></td>
<td><?= htmlspecialchars($req['address']) ?></td>
<td><?= htmlspecialchars($req['city'] ?? '-') ?></td>
<td><?= htmlspecialchars($req['province'] ?? '-') ?></td>
<td><?= htmlspecialchars($req['cellphone_number'] ?? '-') ?></td>
<td>
<?php 
if ($req['user_confirmed'] == 1) {
    echo 'User Confirmed';
} else {
    echo htmlspecialchars($req['status']);
}
?>
</td>
<td><?php if ($req['status'] == 'Pending' && in_array($admin_role, ['superadmin','admin'])): ?><button class="btn btn-confirm" onclick="openModal('confirm-<?= $req['id'] ?>')"> Confirm </button>
<form method="post" style="display:inline;" onsubmit="return confirm('Decline this request?')">
<input type="hidden" name="request_id" value="<?= $req['id'] ?>">
<input type="hidden" name="action" value="Declined">
<button class="btn btn-decline" type="submit"> Decline </button>
</form><?php else: ?><em>No action</em><?php endif; ?></td>
<td><a href="request_info.php?id=<?= $req['id'] ?>" class="btn btn-info"> Info </a></td>
</tr>
<div class="modal" id="modal-confirm-<?= $req['id'] ?>">
<div class="modal-content">
<span class="close-btn" onclick="closeModal('confirm-<?= $req['id'] ?>')">&times;</span>
<h3>Confirm Maintenance</h3>
<form method="POST">
<input type="hidden" name="request_id" value="<?= $req['id'] ?>">
<label>Needed Parts/Tools</label>
<textarea name="admin_remarks" required></textarea>
<label>Estimated Cost (₱)</label>
<input type="number" name="estimated_cost" step="0.01" required>
<label>Maintenance Date</label>
<input type="datetime-local" name="maintenance_date" required>
<button type="submit" name="submit_parts" class="btn btn-confirm" style="margin-top:10px;">Confirm Request</button>
</form>
</div>
</div>
<?php endforeach; ?>
</tbody>
</table>
</div>
<script>
document.addEventListener("DOMContentLoaded", function(){const flash=document.getElementById("flash-message");if(flash){setTimeout(()=>{flash.style.transition="opacity 0.5s ease";flash.style.opacity="0";setTimeout(()=>flash.remove(),500)},3500)}});
</script>
</body>
</html>

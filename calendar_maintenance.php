<?php
session_start();
include 'db.php';

$current_page = basename($_SERVER['PHP_SELF']);

// Fetch all confirmed or approved maintenance requests
$stmt = $pdo->prepare("
    SELECT mr.*, u.firstname, u.lastname, u.email, mr.product_name, mr.brand_name
    FROM maintenance_requests mr
    JOIN users u ON mr.user_id = u.id
    WHERE mr.status IN ('Confirmed', 'Approved')
    ORDER BY mr.preferred_datetime ASC
");
$stmt->execute();
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Maintenance Calendar - Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
  <style>
    /* Sidebar & Main Styling (copied from your theme) */
    body { font-family: Arial, sans-serif; background: #f4f4f4; margin:0; }
    .sidebar { width: 220px; float: left; background: #fff; height: 100vh; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1);}
    .sidebar a { display:block; margin:10px 0; color:#000; text-decoration:none;}
    .sidebar a.active { font-weight:bold; color: black;}
    .main { margin-left: 240px; padding: 20px;}

    /* Calendar container */
    #calendar {
      max-width: 900px;
      margin: 0 auto;
      background: #fff;
      padding: 15px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    /* Modal styling (copied from your table modals) */
    .modal-overlay { display: none; position: fixed; top: 0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.6); z-index: 999; justify-content:center; align-items:center;}
    .modal-overlay.active { display: flex; }
    .modal { background: #fff; padding: 25px; border-radius: 10px; width: 100%; max-width: 500px; position: relative; box-shadow: 0 10px 25px rgba(0,0,0,0.2); max-height:90vh; overflow-y:auto; }
    .modal-header { font-size:18px; font-weight:bold; margin-bottom:15px; text-align:center; }
    .close-btn { position:absolute; right:15px; top:10px; font-size:20px; color:#000; cursor:pointer; }
    .close-btn:hover { color:#666; }
  </style>
  <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            themeSystem: 'standard',
            height: 650,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: [
                <?php foreach($requests as $req): 
                    $title = htmlspecialchars($req['product_name'] . ' (' . $req['brand_name'] . ')'); 
                    $start = date("Y-m-d\TH:i:s", strtotime($req['preferred_datetime']));
                ?>
                {
                    title: '<?= $title ?>',
                    start: '<?= $start ?>',
                    extendedProps: {
                        user: '<?= htmlspecialchars($req['firstname'] . ' ' . $req['lastname']) ?>',
                        email: '<?= htmlspecialchars($req['email']) ?>',
                        issue: '<?= nl2br(htmlspecialchars($req['issue'])) ?>',
                        remarks: '<?= nl2br(htmlspecialchars($req['admin_remarks'])) ?>',
                        cost: '₱<?= number_format($req['estimated_cost'],2) ?>'
                    }
                },
                <?php endforeach; ?>
            ],
            eventClick: function(info) {
                var modal = document.getElementById('eventModal');
                document.getElementById('modalTitle').innerHTML = info.event.title;
                document.getElementById('modalUser').innerHTML = info.event.extendedProps.user;
                document.getElementById('modalEmail').innerHTML = info.event.extendedProps.email;
                document.getElementById('modalIssue').innerHTML = info.event.extendedProps.issue;
                document.getElementById('modalRemarks').innerHTML = info.event.extendedProps.remarks;
                document.getElementById('modalCost').innerHTML = info.event.extendedProps.cost;
                modal.classList.add('active');
            }
        });

        calendar.render();
    });

    function closeModal() {
        document.getElementById('eventModal').classList.remove('active');
    }
  </script>
</head>
<body>
  <div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a>
    <a href="products.php" class="<?= $current_page == 'products.php' ? 'active' : '' ?>"><i class="fas fa-box"></i> Products</a>
    <a href="orders.php" class="<?= $current_page == 'orders.php' ? 'active' : '' ?>"><i class="fas fa-shopping-cart"></i> Orders</a>
    <a href="customers.php" class="<?= $current_page == 'customers.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> Customers</a>
    <a href="admin_newsletter.php" class="<?= $current_page == 'admin_newsletter.php' ? 'active' : '' ?>"><i class="fas fa-envelope"></i> Newsletter</a>
    <a href="maintenance_request.php" class="<?= $current_page == 'maintenance_request.php' ? 'active' : '' ?>"><i class="fas fa-tools"></i> Maintenance Request</a>
    <a href="calendar_maintenance.php" class="<?= $current_page == 'calendar_maintenance.php' ? 'active' : '' ?>"><i class="fas fa-calendar-alt"></i> Calendar</a>
  </div>

  <div class="main">
    <h1><i class="fas fa-calendar-alt"></i> Maintenance Calendar</h1>
    <div id='calendar'></div>
  </div>

  <!-- Modal for event details -->
  <div class="modal-overlay" id="eventModal">
    <div class="modal">
      <span class="close-btn" onclick="closeModal()">×</span>
      <div class="modal-header" id="modalTitle"></div>
      <p><b>User:</b> <span id="modalUser"></span></p>
      <p><b>Email:</b> <span id="modalEmail"></span></p>
      <p><b>Issue:</b><br><span id="modalIssue"></span></p>
      <p><b>Parts/Tools:</b><br><span id="modalRemarks"></span></p>
      <p><b>Estimated Cost:</b> <span id="modalCost"></span></p>
    </div>
  </div>
</body>
</html>

<?php
session_start();
include 'db.php';

// Get the search query
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// Prepare SQL with LIKE for product_name, email, or status
$sql = "SELECT * FROM payments 
        WHERE product_name LIKE :q 
           OR email LIKE :q 
           OR status LIKE :q 
        ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':q' => "%$q%"]);
$orders = $stmt->fetchAll();

function addWeekdays($startDate, $days) {
    $date = new DateTime($startDate);
    $added = 0;
    while ($added < $days) {
        $date->modify('+1 day');
        if (!in_array($date->format('N'), [6, 7])) $added++;
    }
    return $date->format('Y-m-d');
}

$i = 1;
foreach ($orders as $order):
    $date_paid = date('Y-m-d', strtotime($order['created_at']));
    $est_delivery = addWeekdays($date_paid, 5);
?>
<tr>
  <td><?= $i++ ?></td>
  <td><?= htmlspecialchars($order['product_name']) ?></td>
  <td><?= htmlspecialchars($order['quantity']) ?></td>
  <td>₱<?= number_format($order['total_amount'],2) ?></td>
  <td><?= htmlspecialchars($order['email']) ?></td>
  <td><?= strtoupper(htmlspecialchars($order['payment_method'])) ?></td>
  <td><?= $date_paid ?></td>
  <td><?= $est_delivery ?></td>
  <td><?= htmlspecialchars($order['status'] ?? 'pending') ?></td>
  <td class="actions">
    <form method="post" action="orders.php">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <button type="submit" name="action" value="confirm" class="btn">Confirm</button>
    </form>
    <form method="post" action="orders.php">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <button type="submit" name="action" value="cancel" class="btn">Cancel</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>

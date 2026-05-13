<?php
session_start();
include 'db.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q !== '') {
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE firstname LIKE ? 
           OR lastname LIKE ? 
           OR email LIKE ? 
           OR username LIKE ?
        ORDER BY created_at DESC
    ");
    $searchTerm = "%$q%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
} else {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
}

$users = $stmt->fetchAll();

if ($users) {
    foreach ($users as $user) {
        ?>
        <tr>
            <td><?= $user['id'] ?></td>
            <td><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= htmlspecialchars($user['username']) ?></td>
            <td>
                <span class="badge <?= $user['is_verified'] ? 'verified' : 'not-verified' ?>">
                    <?= $user['is_verified'] ? 'Verified' : 'Not Verified' ?>
                </span>
            </td>
            <td><?= $user['created_at'] ?></td>
            <td class="actions">
                <a href="customer_history.php?id=<?= $user['id'] ?>" class="btn-history">
                    <i class="fas fa-clock"></i> History
                </a>
                <a href="customers.php?delete=<?= $user['id'] ?>" 
                   class="btn-delete" 
                   onclick="return confirm('Are you sure you want to delete this customer?');">
                   <i class="fas fa-trash"></i> Delete
                </a>
            </td>
        </tr>
        <?php
    }
} else {
    echo "<tr><td colspan='7'>No customers found.</td></tr>";
}

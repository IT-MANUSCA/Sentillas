<?php
session_name("admin_session");
session_start();
require 'db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_id'])) {
    $claim_id = intval($_POST['claim_id']);
    $stmt = $pdo->prepare("UPDATE warranty_claims SET status = 'claimed', claim_date = NOW() WHERE id = ?");
    if ($stmt->execute([$claim_id])) {
        $_SESSION['flash'] = [
            'message' => 'Warranty claim marked as claimed successfully.',
            'type' => 'success'
        ];
    } else {
        $_SESSION['flash'] = [
            'message' => 'Failed to update warranty claim.',
            'type' => 'error'
        ];
    }
}

header('Location: warranty_codes.php');
exit();

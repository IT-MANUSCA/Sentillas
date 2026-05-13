<?php
session_name("admin_session");
session_start();
require 'db.php';
require_once __DIR__ . '/fpdf/fpdf.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

try {
    // --- Revenue: Total and Orders ---
    $total_orders = $pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();
    $pending_orders = $pdo->query("SELECT COUNT(*) FROM payments WHERE status='pending'")->fetchColumn();
    $confirmed_orders = $pdo->query("SELECT COUNT(*) FROM payments WHERE status='confirmed'")->fetchColumn();
    $cancelled_orders = $pdo->query("SELECT COUNT(*) FROM payments WHERE status='cancelled'")->fetchColumn();

    // Total Revenue (confirmed only)
    $total_revenue = $pdo->query("
        SELECT IFNULL(SUM(total_amount), 0) 
        FROM payments 
        WHERE status = 'confirmed'
    ")->fetchColumn();

    // Recent Revenue Transactions
    $recent_orders = $pdo->query("
        SELECT payments.id, users.firstname, users.lastname, payments.total_amount, payments.status 
        FROM payments 
        LEFT JOIN users ON payments.user_id = users.id 
        ORDER BY payments.id DESC LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Monthly Revenue (last 6 months)
    $monthly_sales = $pdo->query("
        SELECT DATE_FORMAT(payment_date, '%M %Y') AS month, 
               SUM(total_amount) AS total,
               MIN(payment_date) AS min_date
        FROM payments
        WHERE status = 'confirmed' AND payment_date IS NOT NULL
        GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
        ORDER BY min_date DESC
        LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Best-selling Products (Top 5)
    $best_sellers = $pdo->query("
        SELECT product_name, COUNT(*) AS orders
        FROM payments
        WHERE status = 'confirmed' AND product_name IS NOT NULL
        GROUP BY product_name
        ORDER BY orders DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Create PDF
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();

    // Header
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Sentillas Airconditioning Revenue Report', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 6, 'Generated on: ' . date('F d, Y h:i A'), 0, 1, 'C');
    $pdf->Ln(3);

    // Summary
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 8, 'Revenue Summary', 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 7, "Total Orders: $total_orders", 0, 1);
    $pdf->Cell(0, 7, "Pending Orders: $pending_orders", 0, 1);
    $pdf->Cell(0, 7, "Confirmed Orders: $confirmed_orders", 0, 1);
    $pdf->Cell(0, 7, "Cancelled Orders: $cancelled_orders", 0, 1);
    $pdf->Cell(0, 7, "Total Revenue (Confirmed): PHP " . number_format($total_revenue, 2), 0, 1);
    $pdf->Ln(6);

    // Recent Revenue Table
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(0, 8, 'Recent Transactions', 0, 1);

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(18, 8, 'ID', 1, 0, 'C');
    $pdf->Cell(72, 8, 'Customer', 1, 0, 'C');
    $pdf->Cell(35, 8, 'Amount (PHP)', 1, 0, 'C');
    $pdf->Cell(35, 8, 'Status', 1, 0, 'C');
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 11);
    if (count($recent_orders) > 0) {
        foreach ($recent_orders as $order) {
            $customer = trim(($order['firstname'] ?? '') . ' ' . ($order['lastname'] ?? ''));
            if ($customer === '') $customer = 'Guest / Unknown';

            $pdf->Cell(18, 8, $order['id'], 1, 0, 'C');
            $pdf->Cell(72, 8, mb_strimwidth($customer, 0, 36, '...'), 1, 0, 'L');
            $pdf->Cell(35, 8, number_format($order['total_amount'], 2), 1, 0, 'R');
            $pdf->Cell(35, 8, ucfirst($order['status']), 1, 0, 'C');
            $pdf->Ln();
        }
    } else {
        $pdf->Cell(160, 8, 'No transactions found.', 1, 1, 'C');
    }

    $pdf->Ln(8);

    // Monthly Revenue
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(0, 8, 'Monthly Revenue (Last 6 Months)', 0, 1);

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(100, 8, 'Month', 1, 0, 'C');
    $pdf->Cell(60, 8, 'Revenue (PHP)', 1, 0, 'C');
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 11);
    if (count($monthly_sales) > 0) {
        foreach ($monthly_sales as $sale) {
            $pdf->Cell(100, 8, $sale['month'], 1, 0, 'L');
            $pdf->Cell(60, 8, number_format($sale['total'], 2), 1, 0, 'R');
            $pdf->Ln();
        }
    } else {
        $pdf->Cell(160, 8, 'No monthly revenue data available.', 1, 1, 'C');
    }

    $pdf->Ln(8);

    // Best Sellers
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(0, 8, 'Top 5 Best-Selling Products', 0, 1);

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(110, 8, 'Product Name', 1, 0, 'C');
    $pdf->Cell(40, 8, 'Orders', 1, 0, 'C');
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 11);
    if (count($best_sellers) > 0) {
        foreach ($best_sellers as $item) {
            $pdf->Cell(110, 8, mb_strimwidth($item['product_name'], 0, 60, '...'), 1, 0, 'L');
            $pdf->Cell(40, 8, $item['orders'], 1, 0, 'C');
            $pdf->Ln();
        }
    } else {
        $pdf->Cell(150, 8, 'No best-selling product data available.', 1, 1, 'C');
    }

    // Output PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="revenue_report.pdf"');
    $pdf->Output('I', 'revenue_report.pdf');

} catch (Exception $e) {
    echo "Error generating revenue report: " . htmlspecialchars($e->getMessage());
}
?>

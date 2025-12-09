<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

// Get today's date
$today = date('Y-m-d');

// Today's sales summary
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as transaction_count,
        COALESCE(SUM(total_amount), 0) as total_sales
    FROM sales 
    WHERE DATE(created_at) = ?
");
$stmt->execute([$today]);
$summary = $stmt->fetch();

// Recent sales (last 10)
$stmt = $pdo->prepare("
    SELECT 
        s.id,
        s.total_amount,
        s.payment_method,
        s.created_at,
        c.name as client_name,
        c.phone as client_phone
    FROM sales s
    LEFT JOIN clients c ON s.client_id = c.id
    WHERE s.payment_status = 'paid'
    ORDER BY s.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_sales = $stmt->fetchAll();

// Mask phone numbers for privacy
foreach ($recent_sales as &$sale) {
    if (!empty($sale['client_phone'])) {
        $phone = preg_replace('/\D/', '', $sale['client_phone']);
        if (strlen($phone) >= 10) {
            $sale['client_phone'] = substr($phone, 0, 4) . '***' . substr($phone, -3);
        } else {
            $sale['client_phone'] = '****';
        }
    }
}

echo json_encode([
    'summary' => $summary,
    'recent_sales' => $recent_sales,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
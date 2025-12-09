<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
require_once '../includes/db.php';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

$stmt = $pdo->prepare("
    SELECT 
        ic.created_at,
        p.name AS product_name,
        ic.expected_count,
        ic.actual_count,
        ic.variance,
        u.name AS user_name
    FROM inventory_counts ic
    JOIN products p ON ic.product_id = p.id
    JOIN users u ON ic.user_id = u.id
    WHERE DATE(ic.created_at) BETWEEN ? AND ?
    ORDER BY ic.created_at DESC
");
$stmt->execute([$start_date, $end_date]);
$counts = $stmt->fetchAll();

// Output CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="cycle_counts_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Date', 'Product', 'Expected', 'Actual', 'Variance', 'Staff']);

foreach ($counts as $c) {
    fputcsv($output, [
        $c['created_at'],
        $c['product_name'],
        $c['expected_count'],
        $c['actual_count'],
        $c['variance'],
        $c['user_name']
    ]);
}

fclose($output);
exit();
?>
<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);

// Get all stock products
$stmt = $pdo->query("
    SELECT 
        name, 
        barcode, 
        stock_qty, 
        cost_price, 
        selling_price,
        (stock_qty * cost_price) as cost_value,
        (stock_qty * selling_price) as retail_value
    FROM products 
    WHERE type = 'product'
    ORDER BY name
");
$products = $stmt->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="zaina_stock_report_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Product Name', 'Barcode', 'Stock Qty', 'Cost Price', 'Selling Price', 'Total Cost Value', 'Total Retail Value']);

foreach ($products as $p) {
    fputcsv($output, [
        $p['name'],
        $p['barcode'],
        $p['stock_qty'],
        number_format($p['cost_price'], 2),
        number_format($p['selling_price'], 2),
        number_format($p['cost_value'], 2),
        number_format($p['retail_value'], 2)
    ]);
}

fclose($output);
exit;
?>
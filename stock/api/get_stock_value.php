<?php
require_once '../../includes/auth.php';
requireRole(['admin', 'staff']);

$stmt = $pdo->query("
    SELECT 
        SUM(stock_qty * cost_price) as total_cost,
        SUM(stock_qty * selling_price) as total_retail
    FROM products 
    WHERE type = 'product'
");
$value = $stmt->fetch();

echo json_encode([
    'total_cost' => floatval($value['total_cost']),
    'total_retail' => floatval($value['total_retail']),
    'potential_profit' => floatval($value['total_retail'] - $value['total_cost'])
]);
?>
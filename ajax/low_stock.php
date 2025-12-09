<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

// Get products with stock <= 5 (adjust threshold as needed)
$stmt = $pdo->prepare("
    SELECT name, stock_qty 
    FROM products 
    WHERE type = 'product' 
    AND stock_qty <= 5
    AND stock_qty >= 0
    ORDER BY stock_qty ASC, name ASC
");
$stmt->execute();
$items = $stmt->fetchAll();

echo json_encode(['items' => $items]);
?>
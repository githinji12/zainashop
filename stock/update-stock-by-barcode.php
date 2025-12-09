<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

$barcode = $_GET['barcode'] ?? '';
if (empty($barcode)) {
    echo json_encode(['error' => 'No barcode provided']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Find product by barcode
    $stmt = $pdo->prepare("SELECT id, name, stock_qty FROM products WHERE barcode = ? AND type = 'product'");
    $stmt->execute([$barcode]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo json_encode(['error' => 'Product not found or not a physical item']);
        exit();
    }
    
    // Increase stock by 1
    $newStock = $product['stock_qty'] + 1;
    $stmt = $pdo->prepare("UPDATE products SET stock_qty = ? WHERE id = ?");
    $stmt->execute([$newStock, $product['id']]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'product' => $product['name'],
        'new_stock' => $newStock
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => 'Database error']);
}
?>
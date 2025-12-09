<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff', 'employee']);
require_once '../includes/db.php';

header('Content-Type: application/json');

// Get barcode/QR code from URL
$barcode = $_GET['barcode'] ?? '';

if (empty($barcode)) {
    echo json_encode(['error' => 'No barcode provided']);
    exit();
}

try {
    // Handle QR code for services (format: SERVICE:123)
    if (strpos($barcode, 'SERVICE:') === 0) {
        $serviceId = (int)str_replace('SERVICE:', '', $barcode);
        
        if ($serviceId <= 0) {
            echo json_encode(['error' => 'Invalid service ID']);
            exit();
        }
        
        // Fetch service
        $stmt = $pdo->prepare("
            SELECT id, name, selling_price, 'service' AS type 
            FROM products 
            WHERE id = ? AND type = 'service'
        ");
        $stmt->execute([$serviceId]);
        $product = $stmt->fetch();
        
    } else {
        // Fetch physical product by barcode
        $stmt = $pdo->prepare("
            SELECT id, name, selling_price, type, stock_qty 
            FROM products 
            WHERE barcode = ? 
            AND type = 'product' 
            AND stock_qty > 0
        ");
        $stmt->execute([$barcode]);
        $product = $stmt->fetch();
    }
    
    if ($product) {
        echo json_encode([
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'selling_price' => (float)$product['selling_price'],
            'type' => $product['type'],
            'stock_qty' => $product['type'] === 'product' ? (int)$product['stock_qty'] : null
        ]);
    } else {
        echo json_encode(['error' => 'Product not found or out of stock']);
    }
    
} catch (Exception $e) {
    error_log("Barcode Lookup Error: " . $e->getMessage());
    echo json_encode(['error' => 'System error. Please try again.']);
}
?>
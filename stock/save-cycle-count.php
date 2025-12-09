<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = (int)($input['product_id'] ?? 0);
$actual = (int)($input['actual_count'] ?? 0);
$expected = (int)($input['expected_count'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(['error' => 'Invalid product']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Update stock
    $stmt = $pdo->prepare("UPDATE products SET stock_qty = ? WHERE id = ?");
    $stmt->execute([$actual, $product_id]);
    
    // Log cycle count
    $stmt = $pdo->prepare("
        INSERT INTO inventory_counts (product_id, expected_count, actual_count, variance, user_id, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $variance = $actual - $expected;
    $stmt->execute([$product_id, $expected, $actual, $variance, $input['user_id']]);
    
    $pdo->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => 'Database error']);
}
?>
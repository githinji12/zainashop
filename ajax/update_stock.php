<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$product_id = (int)($_POST['product_id'] ?? 0);
$adjustment = (int)($_POST['adjustment'] ?? 0); // Positive = add, Negative = remove
$reason = trim($_POST['reason'] ?? '');

if ($product_id <= 0 || $adjustment == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    // Update stock
    $stmt = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?");
    $stmt->execute([$adjustment, $product_id]);

    // Log adjustment
    $stmt = $pdo->prepare("INSERT INTO stock_adjustments (product_id, adjustment_type, qty, reason, user_id) VALUES (?, ?, ?, ?, ?)");
    $type = $adjustment > 0 ? 'add' : 'remove';
    $stmt->execute([$product_id, $type, abs($adjustment), $reason, $_SESSION['user_id']]);

    echo json_encode(['success' => true, 'message' => 'Stock updated']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}
?>
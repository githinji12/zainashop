<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$product_id = (int)($_POST['product_id'] ?? 0);
$qty = (int)($_POST['qty'] ?? 1);

if ($product_id <= 0 || $qty <= 0) {
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

// Get product
$stmt = $pdo->prepare("SELECT id, name, selling_price, stock_qty, type FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['error' => 'Product not found']);
    exit;
}

if ($product['type'] === 'product' && $qty > $product['stock_qty']) {
    echo json_encode(['error' => 'Only ' . $product['stock_qty'] . ' in stock']);
    exit;
}

// Initialize cart
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Add or update
$found = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['id'] == $product_id) {
        $item['qty'] += $qty;
        $found = true;
        break;
    }
}
if (!$found) {
    $_SESSION['cart'][] = [
        'id' => $product_id,
        'name' => $product['name'],
        'price' => $product['selling_price'],
        'qty' => $qty
    ];
}

// Recalculate total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['qty'];
}

echo json_encode([
    'success' => true,
    'cart_count' => count($_SESSION['cart']),
    'total' => number_format($total, 2)
]);
?>
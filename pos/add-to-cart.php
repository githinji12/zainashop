<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff', 'employee']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$cart = json_decode($_COOKIE['pos_cart'] ?? '[]', true) ?: [];

// Add product
$existing = false;
foreach ($cart as &$item) {
    if ($item['id'] == $input['id']) {
        $item['qty']++;
        $existing = true;
        break;
    }
}
if (!$existing) {
    $cart[] = [
        'id' => $input['id'],
        'name' => $input['name'],
        'price' => (float)$input['selling_price'],
        'qty' => 1
    ];
}

// Save to cookie (alternative to localStorage for mobile)
setcookie('pos_cart', json_encode($cart), time() + 3600, '/');

echo json_encode(['success' => true]);
?>
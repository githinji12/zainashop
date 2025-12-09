<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$phone = trim($data['phone']);
$cart = $data['cart'];
$user_id = $_SESSION['user_id'];

if (!$phone || !is_array($cart) || empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Find or create client
    $stmt = $pdo->prepare("SELECT id FROM clients WHERE phone = ?");
    $stmt->execute([$phone]);
    $client = $stmt->fetch();

    if (!$client) {
        $stmt = $pdo->prepare("INSERT INTO clients (name, phone) VALUES (?, ?)");
        $stmt->execute(['Walk-in Customer', $phone]);
        $client_id = $pdo->lastInsertId();
    } else {
        $client_id = $client['id'];
    }

    // Calculate total
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['price'] * $item['qty'];
    }

    // Create sale
    $stmt = $pdo->prepare("INSERT INTO sales (client_id, user_id, total_amount, payment_status, phone_for_payment) VALUES (?, ?, ?, 'paid', ?)");
    $stmt->execute([$client_id, $user_id, $total, $phone]);
    $sale_id = $pdo->lastInsertId();

    // Add sale items & update stock
    foreach ($cart as $item) {
        $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$sale_id, $item['id'], $item['qty'], $item['price']]);

        // Deduct stock only for products (not services)
        $stmt = $pdo->prepare("SELECT type FROM products WHERE id = ?");
        $stmt->execute([$item['id']]);
        $type = $stmt->fetch()['type'];

        if ($type === 'product') {
            $stmt = $pdo->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?");
            $stmt->execute([$item['qty'], $item['id']]);
        }
    }

    // Add loyalty points (1 point per KES 100)
    $points = floor($total / 100);
    $stmt = $pdo->prepare("UPDATE clients SET loyalty_points = loyalty_points + ? WHERE id = ?");
    $stmt->execute([$points, $client_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'sale_id' => $sale_id,
        'amount' => $total,
        'message' => 'Sale processed'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
}
?>
<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff', 'employee']);
require_once '../includes/db.php';

// We assume cart is in session (from add_to_cart.php)
if (empty($_SESSION['cart']) || !isset($_POST['phone'])) {
    die('Invalid request');
}

$phone = trim($_POST['phone']);
$cart = $_SESSION['cart'];
$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // Get or create client
    $stmt = $pdo->prepare("SELECT id FROM clients WHERE phone = ?");
    $stmt->execute([$phone]);
    $client = $stmt->fetch();
    $client_id = $client ? $client['id'] : null;

    if (!$client_id) {
        $stmt = $pdo->prepare("INSERT INTO clients (name, phone) VALUES (?, ?)");
        $stmt->execute(['Walk-in', $phone]);
        $client_id = $pdo->lastInsertId();
    }

    // Calculate total
    $total = 0;
    foreach ($cart as $item) $total += $item['price'] * $item['qty'];

    // Create sale
    $stmt = $pdo->prepare("INSERT INTO sales (client_id, user_id, total_amount, payment_status, phone_for_payment) VALUES (?, ?, ?, 'paid', ?)");
    $stmt->execute([$client_id, $user_id, $total, $phone]);
    $sale_id = $pdo->lastInsertId();

    // Add items & update stock
    foreach ($cart as $item) {
        $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$sale_id, $item['id'], $item['qty'], $item['price']]);

        // Deduct stock for products only
        $stmt = $pdo->prepare("SELECT type FROM products WHERE id = ?");
        $stmt->execute([$item['id']]);
        $type = $stmt->fetch()['type'];

        if ($type === 'product') {
            $stmt = $pdo->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?");
            $stmt->execute([$item['qty'], $item['id']]);
        }
    }

    // Add loyalty points
    $points = floor($total / 100);
    $stmt = $pdo->prepare("UPDATE clients SET loyalty_points = loyalty_points + ? WHERE id = ?");
    $stmt->execute([$points, $client_id]);

    $pdo->commit();

    // Clear cart
    unset($_SESSION['cart']);

    // Redirect to receipt
    header("Location: receipt.php?id=$sale_id");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error: " . $e->getMessage());
}
?>
<?php
require_once '../includes/auth.php';
require_once '../includes/mpesa.php';
require_once '../includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$phone = trim($data['phone'] ?? '');
$amount = floatval($data['amount'] ?? 0);
$cart = $data['cart'] ?? [];

// Validate
if (!$phone || $amount <= 0 || empty($cart)) {
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

// Normalize phone to 2547...
$phone254 = preg_replace('/^0/', '254', $phone);
if (substr($phone254, 0, 3) !== '254') {
    $phone254 = '254' . $phone254;
}

try {
    $pdo->beginTransaction();

    // === 1. Get or create client ===
    $stmt = $pdo->prepare("SELECT id FROM clients WHERE phone = ?");
    $stmt->execute([$phone]);
    $client = $stmt->fetch();
    $client_id = $client ? $client['id'] : null;

    if (!$client_id) {
        $stmt = $pdo->prepare("INSERT INTO clients (name, phone) VALUES (?, ?)");
        $stmt->execute(['Walk-in Customer', $phone]);
        $client_id = $pdo->lastInsertId();
    }

    // === 2. Create sale (payment_status = 'pending') ===
    $stmt = $pdo->prepare("INSERT INTO sales (client_id, user_id, total_amount, payment_status, phone_for_payment) VALUES (?, ?, ?, 'pending', ?)");
    $stmt->execute([$client_id, $_SESSION['user_id'], $amount, $phone254]);
    $sale_id = $pdo->lastInsertId();

    // === 3. Save cart items ===
    foreach ($cart as $item) {
        $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$sale_id, $item['id'], $item['qty'], $item['price']]);
    }

    // === 4. Initiate M-Pesa ===
    $response = initiateSTKPush($phone254, $amount, 'ZAINA-' . $sale_id);

    if (isset($response['CheckoutRequestID'])) {
        // === 5. Link CheckoutRequestID to sale (for callback) ===
        $stmt = $pdo->prepare("UPDATE sales SET checkout_request_id = ? WHERE id = ?");
        $stmt->execute([$response['CheckoutRequestID'], $sale_id]);

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'sale_id' => $sale_id
        ]);
    } else {
        $pdo->rollBack();
        echo json_encode(['error' => $response['errorMessage'] ?? 'Payment initiation failed']);
    }

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => 'Transaction failed: ' . $e->getMessage()]);
}
?>
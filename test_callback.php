<?php
require_once 'includes/db.php';
$checkoutID = 'ws_CO_04112025145354164794655521'; // From your log

$stmt = $pdo->prepare("SELECT id, payment_status FROM sales WHERE checkout_request_id = ?");
$stmt->execute([$checkoutID]);
$sale = $stmt->fetch();

if ($sale) {
    echo "FOUND SALE: ID={$sale['id']}, Status={$sale['payment_status']}";
    // Try updating
    $stmt = $pdo->prepare("UPDATE sales SET payment_status = 'paid' WHERE id = ?");
    $stmt->execute([$sale['id']]);
    echo "\n✅ Updated to 'paid'";
} else {
    echo "❌ SALE NOT FOUND";
}
?>
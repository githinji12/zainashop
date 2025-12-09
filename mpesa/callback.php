<?php
/**
 * M-Pesa Daraja API Callback Handler
 * Receives payment confirmation from Safaricom
 * Updates sale status in database using CheckoutRequestID
 */

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Get raw POST data
$input = file_get_contents('php://input');

// Log for debugging
$logFile = __DIR__ . '/logs/callback_' . date('Y-m-d_H-i-s') . '.json';
file_put_contents($logFile, $input);

// Parse JSON
$data = json_decode($input, true);

// Validate structure
if (!isset($data['Body']['stkCallback'])) {
    error_log("Invalid callback format from M-Pesa");
    http_response_code(400);
    exit;
}

$callback = $data['Body']['stkCallback'];
$checkoutRequestId = $callback['CheckoutRequestID'] ?? '';
$resultCode = $callback['ResultCode'] ?? 1;
$resultDesc = $callback['ResultDesc'] ?? 'No description';

// Safaricom expects a 200 response with ResultCode=0
header('Content-Type: application/json');
echo json_encode([
    "ResultCode" => 0,
    "ResultDesc" => "Accepted"
]);

// Process only successful payments
if ($resultCode == 0) {
    // Extract metadata
    $metadata = $callback['CallbackMetadata']['Item'] ?? [];
    $mpesaReceiptNumber = null;
    $phoneNumber = null;

    foreach ($metadata as $item) {
        if ($item['Name'] === 'MpesaReceiptNumber') {
            $mpesaReceiptNumber = $item['Value'];
        } elseif ($item['Name'] === 'PhoneNumber') {
            $phoneNumber = $item['Value'];
        }
    }

    // ✅ LOAD DATABASE
    require_once __DIR__ . '/../includes/db.php';

    try {
        // ✅ FIND SALE BY CHECKOUTREQUESTID (RELIABLE!)
        $stmt = $pdo->prepare("SELECT id FROM sales WHERE checkout_request_id = ? AND payment_status = 'pending'");
        $stmt->execute([$checkoutRequestId]);
        $sale = $stmt->fetch();

        if ($sale) {
            // ✅ UPDATE SALE AS PAID
            $stmt = $pdo->prepare("
                UPDATE sales 
                SET payment_status = 'paid', 
                    mpesa_receipt = ? 
                WHERE id = ?
            ");
            $stmt->execute([$mpesaReceiptNumber, $sale['id']]);
            
            error_log("✅ Payment confirmed: Sale #{$sale['id']} - Receipt: {$mpesaReceiptNumber}");
        } else {
            error_log("⚠️ No pending sale found for CheckoutRequestID: {$checkoutRequestId}");
        }
    } catch (Exception $e) {
        error_log("❌ DB error in callback: " . $e->getMessage());
    }
} else {
    error_log("❌ M-Pesa payment failed: Code {$resultCode} - {$resultDesc}");
}
?>
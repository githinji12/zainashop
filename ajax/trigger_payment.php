<?php
session_start();
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone']; // Client's phone number
    $amount = $_POST['amount'];
    $sale_id = $_POST['sale_id'];

    // SIMULATE: In real app, call M-Pesa API here
    // For now, we simulate success and update DB

    // Update sale as "paid"
    $stmt = $pdo->prepare("UPDATE sales SET payment_status = 'paid', payment_method = 'M-Pesa' WHERE id = ?");
    $stmt->execute([$sale_id]);

    // Log: In real app, you'd use cURL to hit Daraja API
    // Example API call (pseudo):
    /*
    $response = sendStkPush($phone, $amount, 'Zaina Beauty Payment');
    if ($response['success']) {
        // mark as paid
    }
    */

    echo json_encode(['success' => true, 'message' => 'Payment prompt sent to ' . $phone]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
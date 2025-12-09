<?php
require_once '../includes/auth.php';
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request');
}

$amount = (float)$_POST['amount'];
$phone = preg_replace('/^0/', '254', $_POST['phone']); // Convert 0712 → 254712
$account_ref = '40018884'; // Your account number
$transaction_desc = 'Payment for Zaina Beauty';

// Get access token
function getAccessToken() {
    $credentials = base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET);
    $url = "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic {$credentials}"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = json_decode(curl_exec($ch));
    curl_close($ch);
    
    return $result->access_token ?? null;
}

// Initiate STK Push
$token = getAccessToken();
if (!$token) {
    die('Failed to get access token');
}

$timestamp = date('YmdHis');
$password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);

$url = "https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest";
$data = [
    'BusinessShortCode' => MPESA_SHORTCODE,
    'Password' => $password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $amount,
    'PartyA' => $phone,
    'PartyB' => MPESA_SHORTCODE,
    'PhoneNumber' => $phone,
    'CallBackURL' => MPESA_CALLBACK_URL,
    'AccountReference' => $account_ref,
    'TransactionDesc' => $transaction_desc
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = json_decode(curl_exec($ch));
curl_close($ch);

// Log request
file_put_contents(MPESA_LOG_PATH . 'stk_' . time() . '.json', json_encode($response));

if (isset($response->ResponseCode) && $response->ResponseCode == '0') {
    echo json_encode(['success' => true, 'message' => 'Check your phone to complete payment.']);
} else {
    echo json_encode(['success' => false, 'message' => $response->errorMessage ?? 'Payment failed.']);
}
?>
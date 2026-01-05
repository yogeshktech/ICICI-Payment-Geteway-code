<?php
/**
 * Test Callback Handler
 * Simulates payment gateway callback for testing
 */

$orderId = $_POST['orderId'] ?? '';
$amount = $_POST['amount'] ?? '';
$status = $_POST['status'] ?? 'failed';
$paymentMode = $_POST['paymentMode'] ?? 'Credit Card';
$merchantTxnNo = $_POST['merchantTxnNo'] ?? '';

if (!$orderId) {
    die('Invalid order ID');
}

// Generate test transaction ID
$txnId = 'TEST' . time() . rand(1000, 9999);

// Build callback parameters to match real gateway
$callbackParams = [
    'merchantTxnNo' => $merchantTxnNo,
    'orderId' => $orderId,
    'ORDERID' => $orderId
];

// Redirect to actual callback handler with parameters
$queryString = http_build_query($callbackParams);
header('Location: callback.php?' . $queryString);
exit;

<?php
// Test hash calculation to match ICICI gateway's expected hash

$key = '971d6335-a0e0-4b43-8c98-506ae39a53a8';

$payload = [
    'merchantId' => '100000000360859',
    'aggregatorID' => '100000000360858',
    'merchantTxnNo' => 'ORD1766989981510',
    'amount' => '100.00',
    'currencyCode' => '356',
    'payType' => '0',
    'customerEmailID' => 'customer@example.com',
    'transactionType' => 'SALE',
    'returnURL' => 'https://unikbox.in/getway/callback.php',
    'txnDate' => '20251229073302',
    'customerMobileNo' => '919876543210',
    'customerName' => 'Test Customer',
    'addlParam1' => '000',
    'addlParam2' => '111'
];

$expectedHash = 'a838240a445e9db2c72c09b8f318d536ecc690d5a087348331502beb9b330a9d';

echo "Testing different hash combinations to match: $expectedHash\n\n";

// Test with key without hyphens
$keyNoHyphens = '971d6335a0e04b438c98506ae39a53a8';

// Test 1: Without aggregatorID
$hash1 = $payload['addlParam1'] . $payload['addlParam2'] . $payload['amount'] . 
         $payload['currencyCode'] . $payload['customerEmailID'] . 
         $payload['customerMobileNo'] . $payload['customerName'] . 
         $payload['merchantId'] . $payload['merchantTxnNo'] . 
         $payload['payType'] . $payload['returnURL'] . 
         $payload['transactionType'] . $payload['txnDate'];
$result1 = hash_hmac('sha256', $hash1, $keyNoHyphens);
echo "1. Without aggregatorID (key no hyphens):\n   Hash: $result1\n   Match: " . ($result1 === $expectedHash ? 'YES' : 'NO') . "\n\n";

// Test 2: With aggregatorID at start
$hash2 = $payload['aggregatorID'] . $payload['addlParam1'] . $payload['addlParam2'] . 
         $payload['amount'] . $payload['currencyCode'] . $payload['customerEmailID'] . 
         $payload['customerMobileNo'] . $payload['customerName'] . 
         $payload['merchantId'] . $payload['merchantTxnNo'] . 
         $payload['payType'] . $payload['returnURL'] . 
         $payload['transactionType'] . $payload['txnDate'];
$result2 = hash_hmac('sha256', $hash2, $keyNoHyphens);
echo "2. With aggregatorID at start (key no hyphens):\n   Hash: $result2\n   Match: " . ($result2 === $expectedHash ? 'YES' : 'NO') . "\n\n";

// Test 3: merchantId + aggregatorID first
$hash3 = $payload['merchantId'] . $payload['aggregatorID'] . $payload['merchantTxnNo'] .
         $payload['amount'] . $payload['currencyCode'] . $payload['payType'] . 
         $payload['transactionType'] . $payload['customerEmailID'] . 
         $payload['customerMobileNo'] . $payload['customerName'] . 
         $payload['returnURL'] . $payload['txnDate'] . 
         $payload['addlParam1'] . $payload['addlParam2'];
$result3 = hash_hmac('sha256', $hash3, $keyNoHyphens);
echo "3. Different order (key no hyphens):\n   Hash: $result3\n   Match: " . ($result3 === $expectedHash ? 'YES' : 'NO') . "\n\n";

// Test 4: JSON order (as sent)
$jsonOrder = $payload['merchantId'] . $payload['aggregatorID'] . $payload['merchantTxnNo'] .
             $payload['amount'] . $payload['currencyCode'] . $payload['payType'] . 
             $payload['customerEmailID'] . $payload['transactionType'] . 
             $payload['returnURL'] . $payload['txnDate'] . $payload['customerMobileNo'] . 
             $payload['customerName'] . $payload['addlParam1'] . $payload['addlParam2'];
$result4 = hash_hmac('sha256', $jsonOrder, $keyNoHyphens);
echo "4. JSON field order (key no hyphens):\n   Hash: $result4\n   Match: " . ($result4 === $expectedHash ? 'YES' : 'NO') . "\n\n";

// Test with original key with hyphens
echo "=== Testing with key WITH hyphens ===\n\n";
$result5 = hash_hmac('sha256', $hash1, $key);
echo "5. Without aggregatorID (key with hyphens):\n   Hash: $result5\n   Match: " . ($result5 === $expectedHash ? 'YES' : 'NO') . "\n\n";

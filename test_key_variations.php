<?php
// Test with key variations
$keyWithHyphens = '971d6335-a0e0-4b43-8c98-506ae39a53a8';
$keyNoHyphens = '971d6335a0e04b438c98506ae39a53a8';
$expectedHash = '88cf7fc97e3e3a81bd7f714ffa95e95f8fe468dacca1321057b6ebb32d9b5eda';

$p = [
    'addlParam1' => '000',
    'addlParam2' => '111',
    'aggregatorID' => '100000000360858',
    'amount' => '100.00',
    'currencyCode' => '356',
    'customerEmailID' => 'customer@example.com',
    'customerMobileNo' => '919876543210',
    'customerName' => 'Test Customer',
    'merchantId' => '100000000360859',
    'merchantTxnNo' => 'ORD1766990226539',
    'payType' => '0',
    'returnURL' => 'https://unikbox.in/getway/callback.php',
    'transactionType' => 'SALE',
    'txnDate' => '20251229073707'
];

$patterns = [
    $p['addlParam1'].$p['addlParam2'].$p['amount'].$p['currencyCode'].$p['customerEmailID'].
    $p['customerMobileNo'].$p['customerName'].$p['merchantId'].$p['merchantTxnNo'].
    $p['payType'].$p['returnURL'].$p['transactionType'].$p['txnDate'],
    
    $p['aggregatorID'].$p['addlParam1'].$p['addlParam2'].$p['amount'].$p['currencyCode'].
    $p['customerEmailID'].$p['customerMobileNo'].$p['customerName'].$p['merchantId'].
    $p['merchantTxnNo'].$p['payType'].$p['returnURL'].$p['transactionType'].$p['txnDate'],
];

echo "Expected: $expectedHash\n\n";

foreach ($patterns as $i => $hashString) {
    $resultWith = hash_hmac('sha256', $hashString, $keyWithHyphens);
    $resultNo = hash_hmac('sha256', $hashString, $keyNoHyphens);
    
    if ($resultWith === $expectedHash) {
        echo "✓✓✓ MATCH with hyphens, pattern " . ($i+1) . "\n";
        echo "String: $hashString\n";
        break;
    }
    if ($resultNo === $expectedHash) {
        echo "✓✓✓ MATCH without hyphens, pattern " . ($i+1) . "\n";
        echo "String: $hashString\n";
        break;
    }
}

// Maybe the key itself needs to be hashed or encoded differently?
echo "\n--- Testing if it's not HMAC but simple hash ---\n";
foreach ($patterns as $i => $hashString) {
    $test1 = hash('sha256', $hashString . $keyWithHyphens);
    $test2 = hash('sha256', $keyWithHyphens . $hashString);
    
    if ($test1 === $expectedHash) {
        echo "✓ MATCH: hash(string + key)\n";
        break;
    }
    if ($test2 === $expectedHash) {
        echo " MATCH: hash(key + string)\n";
        break;
    }
}

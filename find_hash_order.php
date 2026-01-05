<?php
// Find the correct hash order by testing all common patterns

$key = '971d6335-a0e0-4b43-8c98-506ae39a53a8';
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

// Test different common patterns
$patterns = [
    'Current (no agg)' => 
        $p['addlParam1'].$p['addlParam2'].$p['amount'].$p['currencyCode'].$p['customerEmailID'].
        $p['customerMobileNo'].$p['customerName'].$p['merchantId'].$p['merchantTxnNo'].
        $p['payType'].$p['returnURL'].$p['transactionType'].$p['txnDate'],
    
    'With aggregatorID at start' => 
        $p['aggregatorID'].$p['addlParam1'].$p['addlParam2'].$p['amount'].$p['currencyCode'].
        $p['customerEmailID'].$p['customerMobileNo'].$p['customerName'].$p['merchantId'].
        $p['merchantTxnNo'].$p['payType'].$p['returnURL'].$p['transactionType'].$p['txnDate'],
    
    'merchantId first' => 
        $p['merchantId'].$p['aggregatorID'].$p['merchantTxnNo'].$p['amount'].$p['currencyCode'].
        $p['payType'].$p['transactionType'].$p['customerEmailID'].$p['customerMobileNo'].
        $p['customerName'].$p['returnURL'].$p['txnDate'].$p['addlParam1'].$p['addlParam2'],
    
    'Without addlParams' => 
        $p['aggregatorID'].$p['amount'].$p['currencyCode'].$p['customerEmailID'].
        $p['customerMobileNo'].$p['customerName'].$p['merchantId'].$p['merchantTxnNo'].
        $p['payType'].$p['returnURL'].$p['transactionType'].$p['txnDate'],
    
    'Simple order' => 
        $p['merchantId'].$p['merchantTxnNo'].$p['amount'].$p['currencyCode'].$p['transactionType'],
    
    'With agg after merchant' => 
        $p['merchantId'].$p['aggregatorID'].$p['addlParam1'].$p['addlParam2'].$p['amount'].
        $p['currencyCode'].$p['customerEmailID'].$p['customerMobileNo'].$p['customerName'].
        $p['merchantTxnNo'].$p['payType'].$p['returnURL'].$p['transactionType'].$p['txnDate'],
];

echo "Testing patterns to match: $expectedHash\n\n";

foreach ($patterns as $name => $hashString) {
    $result = hash_hmac('sha256', $hashString, $key);
    $match = ($result === $expectedHash);
    echo ($match ? "✓✓✓ MATCH! " : "    ") . "$name\n";
    if ($match) {
        echo "    Hash: $result\n";
        echo "    String: $hashString\n";
        break;
    }
}

echo "\n--- Testing if aggregatorID should be before merchantId ---\n";
$test = $p['aggregatorID'].$p['merchantId'].$p['merchantTxnNo'].$p['amount'].$p['currencyCode'].
        $p['payType'].$p['customerEmailID'].$p['transactionType'].$p['returnURL'].
        $p['customerMobileNo'].$p['customerName'].$p['txnDate'].$p['addlParam1'].$p['addlParam2'];
$result = hash_hmac('sha256', $test, $key);
echo ($result === $expectedHash ? "✓ MATCH!" : "NO") . " - agg+merchant+merchTxn+amt+curr+payType+email+trans+return+mob+name+date+addl1+addl2\n";

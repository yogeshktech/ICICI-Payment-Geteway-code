<?php
// Comprehensive hash test

$key = '971d6335a0e04b438c98506ae39a53a8';
$expectedHash = 'a838240a445e9db2c72c09b8f318d536ecc690d5a087348331502beb9b330a9d';

$p = [
    'merchantId' => '100000000360859',
    'aggregatorID' => '100000000360858',
    'merchantTxnNo' => 'ORD1766989981510',
    'amount' => '100.00',
    'currencyCode' => '356',
    'payType' => '0',
    'customerEmailID' => 'customer@example.com',
    'transactionType' => 'SALE',
    'returnURL' => 'http://localhost/Getway/callback.php',
    'txnDate' => '20251229073302',
    'customerMobileNo' => '919876543210',
    'customerName' => 'Test Customer',
    'addlParam1' => '000',
    'addlParam2' => '111'
];

$tests = [
    // Strict alphabetical
    'addl1+addl2+agg+amt+curr+custEmail+custMob+custName+merch+merchTxn+payType+return+trans+txnDate' =>
        $p['addlParam1'].$p['addlParam2'].$p['aggregatorID'].$p['amount'].$p['currencyCode'].
        $p['customerEmailID'].$p['customerMobileNo'].$p['customerName'].$p['merchantId'].
        $p['merchantTxnNo'].$p['payType'].$p['returnURL'].$p['transactionType'].$p['txnDate'],
    
    // Merchant first (common pattern)
    'merch+agg+merchTxn+amt+curr+payType+trans+custEmail+custMob+custName+return+txnDate+addl1+addl2' =>
        $p['merchantId'].$p['aggregatorID'].$p['merchantTxnNo'].$p['amount'].$p['currencyCode'].
        $p['payType'].$p['transactionType'].$p['customerEmailID'].$p['customerMobileNo'].
        $p['customerName'].$p['returnURL'].$p['txnDate'].$p['addlParam1'].$p['addlParam2'],
    
    // Without addlParams and aggregatorID
    'amt+curr+custEmail+custMob+custName+merch+merchTxn+payType+return+trans+txnDate' =>
        $p['amount'].$p['currencyCode'].$p['customerEmailID'].$p['customerMobileNo'].
        $p['customerName'].$p['merchantId'].$p['merchantTxnNo'].$p['payType'].
        $p['returnURL'].$p['transactionType'].$p['txnDate'],
    
    // Core fields only
    'merch+agg+merchTxn+amt+curr+payType+trans+return+txnDate' =>
        $p['merchantId'].$p['aggregatorID'].$p['merchantTxnNo'].$p['amount'].
        $p['currencyCode'].$p['payType'].$p['transactionType'].$p['returnURL'].$p['txnDate'],
];

foreach ($tests as $desc => $hashString) {
    $result = hash_hmac('sha256', $hashString, $key);
    $match = ($result === $expectedHash) ? '✓ MATCH!' : 'NO';
    echo "$match - $desc\n";
    if ($result === $expectedHash) {
        echo "   FOUND IT! Hash: $result\n";
        echo "   String: $hashString\n";
        break;
    }
}

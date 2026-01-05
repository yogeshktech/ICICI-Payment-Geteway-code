<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Prevent browser caching of payment result pages
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

$config = require __DIR__ . '/configproduction.php';
require_once __DIR__ . '/db.php';

$mid = $config['mid'];
$aggregatorId = $config['aggregator_id'] ?? '';
$key = $config['key'];
$cmdEndpoint = $config['command_endpoint'];
$db = Database::getInstance();

$raw = file_get_contents('php://input');
$received = $_REQUEST;

@file_put_contents(__DIR__.'/logs/callback_'.time().'.log', "RAW:\n".$raw."\nREQ:\n".print_r($received,true)."\n");

$orderId = $received['merchantTxnNo'] ?? $received['orderId'] ?? $received['ORDERID'] ?? null;
if ($orderId) {
    // Check if test mode is enabled
    if (!empty($config['test_mode'])) {
        // Simulate payment status response
        $testStatus = $_POST['status'] ?? 'success';
        $paymentMode = $_POST['paymentMode'] ?? 'Credit Card';
        
        $res = json_encode([
            'merchantTxnNo' => $orderId,
            'txnID' => 'TEST' . time() . rand(1000, 9999),
            'amount' => $db->getPaymentByOrderId($orderId)['amount'] ?? '0.00',
            'txnStatus' => $testStatus === 'success' ? 'SUC' : 'FAL',
            'responseCode' => $testStatus === 'success' ? '000' : '999',
            'respDescription' => $testStatus === 'success' ? 'Transaction Successful' : 'Transaction Failed',
            'paymentMode' => $paymentMode,
            'authCode' => $testStatus === 'success' ? 'TEST' . rand(100000, 999999) : null,
            'paymentDateTime' => date('Y-m-d H:i:s'),
            'txnRespDescription' => $testStatus === 'success' ? 'Approved' : 'Declined by test simulator'
        ], JSON_UNESCAPED_SLASHES);
    } else {
        $cmdPayload = [
            'merchantId' => $mid,
            'aggregatorID' => $aggregatorId,
            'merchantTxnNo' => $orderId,
            'originalTxnNo' => $orderId,
            'transactionType' => 'STATUS'
        ];
        
        // Hash order for STATUS command: aggregatorID + merchantId + merchantTxnNo + originalTxnNo + transactionType
        $hashString = $cmdPayload['aggregatorID'] . $cmdPayload['merchantId'] . $cmdPayload['merchantTxnNo'] . 
                      $cmdPayload['originalTxnNo'] . $cmdPayload['transactionType'];
        $cmdPayload['secureHash'] = hash_hmac('sha256', $hashString, $key);
        
        // Log hash calculation for debugging
        @file_put_contents(__DIR__.'/logs/status_hash_debug_'.time().'.log', 
            "Hash String: $hashString\nKey: $key\nHash: ".$cmdPayload['secureHash']."\nPayload: ".json_encode($cmdPayload, JSON_UNESCAPED_SLASHES)."\n");
        
        $json = json_encode($cmdPayload, JSON_UNESCAPED_SLASHES);

        $ch = curl_init($cmdEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $res = curl_exec($ch);
        curl_close($ch);
    }

    @file_put_contents(__DIR__.'/logs/status_'.$orderId.'_'.time().'.log', $res);
    
    $statusData = json_decode($res, true);
    $isSuccess = ($statusData['txnStatus'] ?? '') === 'SUC' && ($statusData['responseCode'] ?? '') === '000';
    
    // Update payment record in database
    try {
        $updateData = [
            'transaction_id' => $statusData['txnID'] ?? null,
            'txn_status' => $statusData['txnStatus'] ?? null,
            'response_code' => $statusData['responseCode'] ?? null,
            'response_description' => $statusData['respDescription'] ?? null,
            'payment_mode' => $statusData['paymentMode'] ?? null,
            'auth_code' => $statusData['authCode'] ?? null,
            'payment_status' => $isSuccess ? 'SUCCESS' : 'FAILED',
            'updated_at' => date('Y-m-d H:i:s'),
            'callback_data' => $raw,
            'status_response' => $res
        ];
        
        if (!empty($statusData['paymentDateTime'])) {
            $updateData['payment_datetime'] = date('Y-m-d H:i:s', strtotime($statusData['paymentDateTime']));
        }
        
        $db->updatePaymentRecord($orderId, $updateData);
    } catch (Exception $e) {
        error_log('Failed to update payment record: ' . $e->getMessage());
    }
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Payment <?php echo $isSuccess ? 'Success' : 'Failed'; ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; padding: 20px; }
            .container { max-width: 600px; margin: 50px auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
            .header { padding: 30px; text-align: center; }
            .header.success { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
            .header.failed { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
            .icon { font-size: 60px; margin-bottom: 10px; }
            .header h1 { font-size: 28px; margin-bottom: 5px; }
            .header p { opacity: 0.9; font-size: 16px; }
            .content { padding: 30px; }
            .detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eee; }
            .detail-row:last-child { border-bottom: none; }
            .label { color: #666; font-weight: 500; }
            .value { color: #333; font-weight: 600; text-align: right; }
            .amount { font-size: 32px; color: #667eea; font-weight: bold; text-align: center; margin: 20px 0; }
            .actions { padding: 20px 30px; background: #f9f9f9; text-align: center; }
            .btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: 600; transition: background 0.3s; }
            .btn:hover { background: #5568d3; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header <?php echo $isSuccess ? 'success' : 'failed'; ?>">
                <div class="icon"><?php echo $isSuccess ? '✓' : '✗'; ?></div>
                <h1><?php echo $isSuccess ? 'Payment Successful!' : 'Payment Failed'; ?></h1>
                <p><?php echo htmlspecialchars($statusData['respDescription'] ?? 'Transaction processed'); ?></p>
            </div>
            
            <div class="content">
                <?php if ($isSuccess): ?>
                    <div class="amount">₹<?php echo number_format($statusData['amount'], 2); ?></div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <span class="label">Order ID</span>
                    <span class="value"><?php echo htmlspecialchars($statusData['merchantTxnNo'] ?? 'N/A'); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Transaction ID</span>
                    <span class="value"><?php echo htmlspecialchars($statusData['txnID'] ?? 'N/A'); ?></span>
                </div>
                
                <?php if ($isSuccess): ?>
                <div class="detail-row">
                    <span class="label">Payment Mode</span>
                    <span class="value"><?php echo htmlspecialchars($statusData['paymentMode'] ?? 'N/A'); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Date & Time</span>
                    <span class="value"><?php echo isset($statusData['paymentDateTime']) ? date('d M Y, h:i A', strtotime($statusData['paymentDateTime'])) : 'N/A'; ?></span>
                </div>
                
                <?php if (!empty($statusData['authCode'])): ?>
                <div class="detail-row">
                    <span class="label">Auth Code</span>
                    <span class="value"><?php echo htmlspecialchars($statusData['authCode']); ?></span>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="detail-row">
                    <span class="label">Reason</span>
                    <span class="value"><?php echo htmlspecialchars($statusData['txnRespDescription'] ?? 'Transaction declined'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Response Code</span>
                    <span class="value"><?php echo htmlspecialchars($statusData['txnResponseCode'] ?? $statusData['responseCode'] ?? 'N/A'); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="actions">
                <a href="index.php" class="btn"><?php echo $isSuccess ? 'Make Another Payment' : 'Try Again'; ?></a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

echo "Callback received. Check logs.";
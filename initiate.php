<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

$config = require __DIR__ . '/configproduction.php';
require_once __DIR__ . '/db.php';

$mid = $config['mid'];
$aggregatorId = $config['aggregator_id'] ?? '';
$key = $config['key'];
$endpoint = $config['initiate_endpoint'];
$db = Database::getInstance();

// Ensure aggregatorId is set for production
if (empty($aggregatorId)) {
    http_response_code(500);
    echo 'Error: Aggregator ID not configured. Please update ConfigProduction.php';
    exit;
}

$amount = isset($_POST['amount']) ? trim($_POST['amount']) : null;
$orderId = isset($_POST['order_id']) ? trim($_POST['order_id']) : null;
if (!$amount || !$orderId) {
    http_response_code(400);
    echo 'Missing amount or order id';
    exit;
}

$txnDate = date('YmdHis');

$payload = [
    'merchantId' => $mid,
    'aggregatorID' => $aggregatorId,
    'merchantTxnNo' => $orderId,
    'amount' => number_format((float)$amount, 2, '.', ''),
    'currencyCode' => '356',
    'payType' => '0',
    'customerEmailID' => 'customer@example.com',
    'transactionType' => 'SALE',
    'returnURL' => $config['return_url'],
    'txnDate' => $txnDate,
    'customerMobileNo' => '919876543210',
    'customerName' => 'Test Customer',
    'addlParam1' => '000',
    'addlParam2' => '111'
];

// Hash string order as per ICICI documentation:
// addlParam1 + addlParam2 + aggregatorID + amount + currencyCode + customerEmailID + 
// customerMobileNo + customerName + merchantId + merchantTxnNo + 
// payType + returnURL + transactionType + txnDate
$hashString = $payload['addlParam1'] . $payload['addlParam2'] . $payload['aggregatorID'] . 
              $payload['amount'] . $payload['currencyCode'] . $payload['customerEmailID'] . 
              $payload['customerMobileNo'] . $payload['customerName'] . 
              $payload['merchantId'] . $payload['merchantTxnNo'] . 
              $payload['payType'] . $payload['returnURL'] . 
              $payload['transactionType'] . $payload['txnDate'];

$payload['secureHash'] = hash_hmac('sha256', $hashString, $key);
$json = json_encode($payload, JSON_UNESCAPED_SLASHES);

// Debug logging
@file_put_contents(__DIR__.'/logs/hash_debug_'.time().'.log', "Hash String: $hashString\nKey: $key\nHash: ".$payload['secureHash']."\n");

// Check if test mode is enabled
if (!empty($config['test_mode'])) {
    // Simulate successful gateway response
    $response = json_encode([
        'redirectURI' => 'https://unikbox.in/getway/test_payment.php',
        'tranCtx' => base64_encode($orderId . '|' . $amount),
        'responseCode' => '000',
        'respDescription' => 'Success'
    ], JSON_UNESCAPED_SLASHES);
    $err = '';
    $httpCode = 200;
} else {
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
}

@file_put_contents(__DIR__.'/logs/initiate_'.time().'.log', "REQ:\n".$json."\nRESP:\n".$response."\nERR:\n".$err."\nHTTP_CODE:\n".$httpCode."\n");

// Check for CURL errors
if ($err) {
    showError('Connection Error', 'Failed to connect to payment gateway: ' . $err, $orderId);
}

// Check for HTTP errors (502, 503, etc.)
if ($httpCode >= 500) {
    showError('Gateway Error', 'Payment gateway is currently unavailable (Error ' . $httpCode . '). Please try again later.', $orderId);
}

if ($httpCode !== 200) {
    showError('Request Error', 'Payment gateway returned error code: ' . $httpCode, $orderId);
}

$data = json_decode($response, true);

// Check for invalid JSON response
if (!$data) {
    showError('Response Error', 'Invalid response from payment gateway. Please try again.', $orderId);
}

// Save payment initiation to database
try {
    $dbData = [
        'order_id' => $orderId,
        'merchant_txn_no' => $orderId,
        'amount' => $payload['amount'],
        'currency_code' => $payload['currencyCode'],
        'customer_name' => $payload['customerName'],
        'customer_email' => $payload['customerEmailID'],
        'customer_mobile' => $payload['customerMobileNo'],
        'transaction_type' => $payload['transactionType'],
        'payment_status' => 'INITIATED',
        'addl_param1' => $payload['addlParam1'],
        'addl_param2' => $payload['addlParam2'],
        'initiated_at' => date('Y-m-d H:i:s'),
        'return_url' => $payload['returnURL'],
        'initiate_request' => $json,
        'initiate_response' => $response,
        'redirect_uri' => $data['redirectURI'] ?? null
    ];
    
    $db->insertPaymentRecord($dbData);
} catch (Exception $e) {
    error_log('Failed to save payment record: ' . $e->getMessage());
}

if (!empty($data['redirectURI']) && !empty($data['tranCtx'])) {
    $redirectUrl = $data['redirectURI'] . '?tranCtx=' . urlencode($data['tranCtx']);
    header('Location: ' . $redirectUrl);
    exit;
}

showError('Gateway Response Error', 'Payment gateway did not provide redirect URL. Response: ' . htmlspecialchars(print_r($data, true)), $orderId);

function showError($title, $message, $orderId) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title><?php echo htmlspecialchars($title); ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; padding: 20px; }
            .container { max-width: 600px; margin: 50px auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
            .header { padding: 30px; text-align: center; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
            .icon { font-size: 60px; margin-bottom: 10px; }
            .header h1 { font-size: 28px; margin-bottom: 5px; }
            .content { padding: 30px; }
            .error-message { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin-bottom: 20px; color: #856404; }
            .detail-row { padding: 12px 0; border-bottom: 1px solid #eee; }
            .detail-row:last-child { border-bottom: none; }
            .label { color: #666; font-weight: 500; margin-bottom: 5px; }
            .value { color: #333; font-family: monospace; background: #f5f5f5; padding: 10px; border-radius: 4px; word-break: break-all; }
            .actions { padding: 20px 30px; background: #f9f9f9; text-align: center; }
            .btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: 600; transition: background 0.3s; margin: 5px; }
            .btn:hover { background: #5568d3; }
            .btn-secondary { background: #6c757d; }
            .btn-secondary:hover { background: #5a6268; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="icon">⚠️</div>
                <h1><?php echo htmlspecialchars($title); ?></h1>
                <p>Payment Initialization Failed</p>
            </div>
            
            <div class="content">
                <div class="error-message">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                
                <div class="detail-row">
                    <div class="label">Order ID</div>
                    <div class="value"><?php echo htmlspecialchars($orderId); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="label">Timestamp</div>
                    <div class="value"><?php echo date('Y-m-d H:i:s'); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="label">Common Solutions</div>
                    <div class="value" style="background: white; font-family: inherit;">
                         Check your internet connection<br>
                         Verify payment gateway credentials<br>
                        • Gateway may be under maintenance<br>
                        • Try again after a few minutes
                    </div>
                </div>
            </div>
            
            <div class="actions">
                <a href="index.php" class="btn">Try Again</a>
                <a href="history.php" class="btn btn-secondary">View History</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
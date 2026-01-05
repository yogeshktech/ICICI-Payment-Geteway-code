<?php
/**
 * Test Payment Simulator
 * Simulates the payment gateway page for testing purposes
 */

$tranCtx = $_GET['tranCtx'] ?? '';
if (!$tranCtx) {
    die('Invalid transaction context');
}

// Decode transaction context
$decoded = base64_decode($tranCtx);
list($orderId, $amount) = explode('|', $decoded);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Payment Gateway</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 500px; width: 100%; background: white; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .badge { display: inline-block; background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; font-size: 12px; margin-top: 10px; }
        .content { padding: 30px; }
        .amount-display { text-align: center; margin-bottom: 30px; }
        .amount-label { color: #666; font-size: 14px; margin-bottom: 5px; }
        .amount-value { font-size: 48px; font-weight: bold; color: #667eea; }
        .detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eee; }
        .detail-row:last-child { border-bottom: none; }
        .label { color: #666; font-weight: 500; }
        .value { color: #333; font-weight: 600; }
        .payment-options { margin: 30px 0; }
        .payment-options h3 { font-size: 16px; margin-bottom: 15px; color: #333; }
        .option { display: flex; align-items: center; padding: 15px; border: 2px solid #e0e0e0; border-radius: 8px; margin-bottom: 10px; cursor: pointer; transition: all 0.3s; }
        .option:hover { border-color: #667eea; background: #f5f7ff; }
        .option input[type="radio"] { margin-right: 12px; width: 20px; height: 20px; cursor: pointer; }
        .option label { flex: 1; cursor: pointer; font-weight: 500; }
        .actions { display: flex; gap: 10px; margin-top: 30px; }
        .btn { flex: 1; padding: 15px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; text-align: center; text-decoration: none; display: inline-block; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-fail { background: #dc3545; color: white; }
        .btn-fail:hover { background: #c82333; }
        .info { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #856404; font-size: 14px; }
        .info strong { display: block; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Test Payment Gateway</h1>
            <p>Simulate payment for testing</p>
            <div class="badge">TEST MODE</div>
        </div>
        
        <div class="content">
            <div class="info">
                <strong>⚠️ Test Mode Active</strong>
                This is a simulated payment page. No real transaction will occur.
            </div>
            
            <div class="amount-display">
                <div class="amount-label">Payment Amount</div>
                <div class="amount-value">₹<?php echo number_format($amount, 2); ?></div>
            </div>
            
            <div class="detail-row">
                <span class="label">Order ID</span>
                <span class="value"><?php echo htmlspecialchars($orderId); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="label">Merchant</span>
                <span class="value">Test Merchant</span>
            </div>
            
            <form id="paymentForm" method="POST" action="test_callback.php">
                <input type="hidden" name="orderId" value="<?php echo htmlspecialchars($orderId); ?>">
                <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
                <input type="hidden" name="merchantTxnNo" value="<?php echo htmlspecialchars($orderId); ?>">
                
                <div class="payment-options">
                    <h3>Select Payment Method</h3>
                    
                    <div class="option">
                        <input type="radio" id="card" name="paymentMode" value="Credit Card" checked>
                        <label for="card"> Credit/Debit Card</label>
                    </div>
                    
                    <div class="option">
                        <input type="radio" id="netbanking" name="paymentMode" value="Net Banking">
                        <label for="netbanking">🏦 Net Banking</label>
                    </div>
                    
                    <div class="option">
                        <input type="radio" id="upi" name="paymentMode" value="UPI">
                        <label for="upi">📱 UPI</label>
                    </div>
                    
                    <div class="option">
                        <input type="radio" id="wallet" name="paymentMode" value="Wallet">
                        <label for="wallet">👛 Wallet</label>
                    </div>
                </div>
                
                <div class="actions">
                    <button type="submit" name="status" value="success" class="btn btn-success">✓ Pay Successfully</button>
                    <button type="submit" name="status" value="failed" class="btn btn-fail">✗ Simulate Failure</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

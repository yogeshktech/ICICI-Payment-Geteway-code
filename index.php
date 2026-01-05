<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

$config = require __DIR__ . '/configproduction.php';
$testMode = !empty($config['test_mode']);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Gateway</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; display: flex; align-items: center; justify-content: center; }
    .container { max-width: 500px; width: 100%; background: white; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); overflow: hidden; }
    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
    .header h1 { font-size: 28px; margin-bottom: 5px; }
    .header p { opacity: 0.9; font-size: 14px; }
    <?php if ($testMode): ?>
    .test-badge { display: inline-block; background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; font-size: 12px; margin-top: 10px; }
    .test-notice { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 20px; border-radius: 8px; color: #856404; font-size: 14px; }
    <?php endif; ?>
    .content { padding: 30px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 14px; }
    .form-group input { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; transition: border-color 0.3s; }
    .form-group input:focus { outline: none; border-color: #667eea; }
    .btn-submit { width: 100%; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.2s; }
    .btn-submit:hover { transform: translateY(-2px); }
    .btn-submit:active { transform: translateY(0); }
    .links { text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0; }
    .links a { color: #667eea; text-decoration: none; font-weight: 600; margin: 0 10px; }
    .links a:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>💳 Payment Unikbox</h1>
      <p>Secure payment processing</p>
      <?php if ($testMode): ?>
      <div class="test-badge">🧪 TEST MODE</div>
      <?php endif; ?>
    </div>
    
    <?php if ($testMode): ?>
    <div class="test-notice">
      <strong>⚠️ Test Mode Active</strong><br>
      Payments will be simulated. No real transactions will occur.
    </div>
    <?php endif; ?>
    
    <div class="content">
      <form action="initiate.php" method="post">
        <div class="form-group">
          <label>Order ID</label>
          <input name="order_id" value="<?php echo 'ORD' . time() . rand(100,999); ?>" readonly required/>
        </div>
        
        <div class="form-group">
          <label>Amount (INR)</label>
          <input name="amount" type="number" step="0.01" min="100" value="100" placeholder="Enter amount (min ₹100)" required/>
          <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">Minimum amount: ₹100.00</small>
        </div>
        
        <button type="submit" class="btn-submit">Proceed to Pay</button>
      </form>
      
      <!--<div class="links">-->
      <!--  <a href="history.php">📊 Payment History</a>-->
      <!--</div>-->
    </div>
  </div>
</body>
</html>
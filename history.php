<?php
require_once __DIR__ . '/db.php';

try {
    $db = Database::getInstance();
    
    // Get filter parameters
    $status = $_GET['status'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    
    $filters = [];
    if ($status) {
        $filters['payment_status'] = $status;
    }
    if ($dateFrom) {
        $filters['date_from'] = $dateFrom . ' 00:00:00';
    }
    if ($dateTo) {
        $filters['date_to'] = $dateTo . ' 23:59:59';
    }
    
    $payments = $db->getPayments($filters, 100, 0);
} catch (Exception $e) {
    $error = 'Failed to fetch payment records: ' . $e->getMessage();
    $payments = [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; }
        .header h1 { margin: 0 0 10px 0; font-size: 32px; }
        .header p { margin: 0; opacity: 0.9; }
        .filters { padding: 20px 30px; background: #f9f9f9; border-bottom: 1px solid #e0e0e0; }
        .filters form { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .filter-group { flex: 1; min-width: 150px; }
        .filter-group label { display: block; margin-bottom: 5px; font-size: 14px; color: #666; font-weight: 500; }
        .filter-group select, .filter-group input { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .filter-group button { padding: 9px 20px; background: #667eea; color: white; border: none; border-radius: 5px; font-weight: 600; cursor: pointer; transition: background 0.3s; }
        .filter-group button:hover { background: #5568d3; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; padding: 30px; }
        .stat-card { padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px; }
        .stat-card.success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-card.failed { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.pending { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .stat-label { font-size: 14px; opacity: 0.9; margin-bottom: 5px; }
        .stat-value { font-size: 28px; font-weight: bold; }
        .table-container { overflow-x: auto; padding: 0 30px 30px 30px; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #f5f5f5; padding: 15px; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #ddd; }
        tbody td { padding: 15px; border-bottom: 1px solid #eee; }
        tbody tr:hover { background: #f9f9f9; }
        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .status-badge.success { background: #d4edda; color: #155724; }
        .status-badge.failed { background: #f8d7da; color: #721c24; }
        .status-badge.initiated { background: #fff3cd; color: #856404; }
        .amount { font-weight: 600; color: #667eea; }
        .no-data { text-align: center; padding: 60px 20px; color: #999; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 20px 30px; border-radius: 5px; border: 1px solid #f5c6cb; }
        .actions { display: flex; gap: 10px; }
        .btn-view { padding: 5px 12px; background: #667eea; color: white; text-decoration: none; border-radius: 4px; font-size: 12px; transition: background 0.3s; }
        .btn-view:hover { background: #5568d3; }
        .back-link { display: inline-block; margin: 20px 30px; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: 600; }
        .back-link:hover { background: #5568d3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Payment History</h1>
            <p>View and manage all payment transactions</p>
        </div>
        
        <div class="filters">
            <form method="GET">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="SUCCESS" <?php echo $status === 'SUCCESS' ? 'selected' : ''; ?>>Success</option>
                        <option value="FAILED" <?php echo $status === 'FAILED' ? 'selected' : ''; ?>>Failed</option>
                        <option value="INITIATED" <?php echo $status === 'INITIATED' ? 'selected' : ''; ?>>Initiated</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>From Date</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                </div>
                <div class="filter-group">
                    <label>To Date</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit">Apply Filters</button>
                </div>
            </form>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php
        // Calculate statistics
        $totalAmount = 0;
        $successCount = 0;
        $failedCount = 0;
        $initiatedCount = 0;
        
        foreach ($payments as $payment) {
            if ($payment['payment_status'] === 'SUCCESS') {
                $successCount++;
                $totalAmount += $payment['amount'];
            } elseif ($payment['payment_status'] === 'FAILED') {
                $failedCount++;
            } elseif ($payment['payment_status'] === 'INITIATED') {
                $initiatedCount++;
            }
        }
        ?>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-label">Total Transactions</div>
                <div class="stat-value"><?php echo count($payments); ?></div>
            </div>
            <div class="stat-card success">
                <div class="stat-label">Successful</div>
                <div class="stat-value"><?php echo $successCount; ?></div>
            </div>
            <div class="stat-card failed">
                <div class="stat-label">Failed</div>
                <div class="stat-value"><?php echo $failedCount; ?></div>
            </div>
            <div class="stat-card pending">
                <div class="stat-label">Pending</div>
                <div class="stat-value"><?php echo $initiatedCount; ?></div>
            </div>
        </div>
        
        <div class="table-container">
            <?php if (empty($payments)): ?>
                <div class="no-data">
                    <h3>No payment records found</h3>
                    <p>Try adjusting your filters or make a new payment</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Mode</th>
                            <th>Customer</th>
                            <th>Date & Time</th>
                            <th>Transaction ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['order_id']); ?></td>
                                <td class="amount">₹<?php echo number_format($payment['amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($payment['payment_status']); ?>">
                                        <?php echo htmlspecialchars($payment['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($payment['payment_mode'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($payment['customer_name'] ?? 'N/A'); ?><br>
                                    <small style="color: #666;"><?php echo htmlspecialchars($payment['customer_email'] ?? ''); ?></small>
                                </td>
                                <td><?php echo date('d M Y, h:i A', strtotime($payment['initiated_at'])); ?></td>
                                <td><small><?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <a href="index.php" class="back-link">← Back to Payment</a>
    </div>
</body>
</html>

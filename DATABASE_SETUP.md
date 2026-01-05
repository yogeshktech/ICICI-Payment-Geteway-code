# Payment Gateway Database Setup Guide

This guide explains how to set up the database for storing payment records with status, timestamps, and other transaction details.

## Database Setup

### Step 1: Create Database and Table

Run the SQL script to create the database and table:

```bash
# Using MySQL command line
mysql -u root -p < database.sql

# Or using phpMyAdmin
# 1. Open phpMyAdmin (http://localhost/phpmyadmin)
# 2. Click on "Import" tab
# 3. Choose the database.sql file
# 4. Click "Go"
```

### Step 2: Configure Database Connection

Update the database credentials in `Config.php`:

```php
'db_host' => 'localhost',      // MySQL host
'db_name' => 'payment_gateway', // Database name
'db_user' => 'root',            // MySQL username
'db_pass' => '',                // MySQL password (empty for XAMPP default)
'db_charset' => 'utf8mb4'       // Character set
```

## Features

### Database Schema

The `payment_records` table includes:

- **Order Information**: order_id, merchant_txn_no, transaction_id
- **Payment Details**: amount, currency_code, payment_status
- **Customer Info**: customer_name, customer_email, customer_mobile
- **Transaction Status**: txn_status, response_code, response_description
- **Payment Gateway Data**: payment_mode, auth_code
- **Timestamps**: initiated_at, updated_at, payment_datetime
- **Raw Data**: initiate_request, initiate_response, callback_data, status_response
- **Audit Timestamps**: created_at, modified_at (auto-managed)

### Payment Status Flow

1. **INITIATED** - Payment request initiated
2. **SUCCESS** - Payment completed successfully
3. **FAILED** - Payment failed or declined

### Available Pages

- **Index .php** - Payment initiation form
- **initiate.php** - Processes payment and saves to database
- **callback.php** - Handles payment gateway callback and updates database
- **history.php** - View all payment transactions with filters

## Usage

### 1. Make a Payment

Visit `http://localhost:8080/Getway/Index .php` and enter payment details.

### 2. View Payment History

Visit `http://localhost:8080/Getway/history.php` to see all payment records.

### 3. Filter Payments

Use the filters on the history page to:
- Filter by payment status (Success/Failed/Initiated)
- Filter by date range
- View statistics (Total, Successful, Failed, Pending)

## Database Helper Functions

The `db.php` file provides the following methods:

```php
$db = Database::getInstance();

// Insert new payment record
$db->insertPaymentRecord($data);

// Update payment record
$db->updatePaymentRecord($orderId, $updateData);

// Get payment by order ID
$payment = $db->getPaymentByOrderId($orderId);

// Get payment by transaction ID
$payment = $db->getPaymentByTxnId($txnId);

// Get all payments with filters
$payments = $db->getPayments($filters, $limit, $offset);
```

## Automatic Data Logging

The system automatically logs:

1. **On Payment Initiation** (initiate.php):
   - Order details
   - Customer information
   - Amount and currency
   - Timestamp of initiation
   - Request/response data

2. **On Payment Callback** (callback.php):
   - Transaction ID from gateway
   - Payment status (SUCCESS/FAILED)
   - Payment mode (Credit Card, Debit Card, etc.)
   - Authorization code
   - Payment timestamp
   - Raw callback data

## Database Indexes

The following indexes are created for optimal query performance:

- `idx_order_id` - Fast lookup by order ID
- `idx_transaction_id` - Fast lookup by transaction ID
- `idx_payment_status` - Fast filtering by status
- `idx_initiated_at` - Fast date-based queries

## Error Handling

- All database operations are wrapped in try-catch blocks
- Errors are logged using PHP's error_log()
- Payment processing continues even if database logging fails
- Check PHP error logs for database-related issues

## Troubleshooting

### Connection Failed
- Verify MySQL is running (Start MySQL in XAMPP)
- Check database credentials in Config.php
- Ensure database 'payment_gateway' exists

### Table Doesn't Exist
- Run the database.sql file to create the table
- Check MySQL user has permissions to create tables

### Data Not Saving
- Check PHP error logs for exceptions
- Verify PDO extension is enabled in php.ini
- Ensure logs/ directory is writable

## Security Notes

- Never commit Config.php with real credentials
- Use prepared statements (already implemented)
- Sanitize all user inputs (already implemented)
- Keep database credentials secure
- Enable SSL for production MySQL connections

## Additional Resources

- Payment history with filters: `history.php`
- Raw transaction logs: `logs/` directory
- Database connection: `db.php`
- Configuration: `Config.php`

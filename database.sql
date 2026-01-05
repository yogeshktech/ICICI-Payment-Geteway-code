-- Create database
CREATE DATABASE IF NOT EXISTS payment_gateway CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE payment_gateway;

-- Create payment_records table
CREATE TABLE IF NOT EXISTS payment_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(100) NOT NULL UNIQUE,
    merchant_txn_no VARCHAR(100) NOT NULL,
    transaction_id VARCHAR(100) DEFAULT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency_code VARCHAR(10) DEFAULT '356',
    
    -- Customer information
    customer_name VARCHAR(255) DEFAULT NULL,
    customer_email VARCHAR(255) DEFAULT NULL,
    customer_mobile VARCHAR(20) DEFAULT NULL,
    
    -- Transaction details
    payment_status VARCHAR(50) DEFAULT 'INITIATED',
    transaction_type VARCHAR(50) DEFAULT 'SALE',
    payment_mode VARCHAR(50) DEFAULT NULL,
    
    -- Payment gateway response
    txn_status VARCHAR(50) DEFAULT NULL,
    response_code VARCHAR(50) DEFAULT NULL,
    response_description TEXT DEFAULT NULL,
    auth_code VARCHAR(100) DEFAULT NULL,
    
    -- Additional parameters
    addl_param1 VARCHAR(255) DEFAULT NULL,
    addl_param2 VARCHAR(255) DEFAULT NULL,
    
    -- Timestamps
    initiated_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    payment_datetime DATETIME DEFAULT NULL,
    
    -- Gateway URLs
    redirect_uri TEXT DEFAULT NULL,
    return_url TEXT DEFAULT NULL,
    
    -- Raw data for debugging
    initiate_request TEXT DEFAULT NULL,
    initiate_response TEXT DEFAULT NULL,
    callback_data TEXT DEFAULT NULL,
    status_response TEXT DEFAULT NULL,
    
    -- Indexes for better query performance
    INDEX idx_order_id (order_id),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_initiated_at (initiated_at),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

<?php
/**
 * Database connection helper
 * Provides PDO connection and helper methods for database operations
 */

class Database {
    private static $instance = null;
    private $connection = null;
    private $config = null;
    
    private function __construct() {
        $this->config = require __DIR__ . '/configproduction.php';
        // Set PHP timezone to match database timezone
        date_default_timezone_set('Asia/Kolkata');
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $this->config['db_host'],
                $this->config['db_name'],
                $this->config['db_charset']
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO(
                $dsn,
                $this->config['db_user'],
                $this->config['db_pass'],
                $options
            );
            // Set timezone to Asia/Kolkata (Indian Standard Time)
            $this->connection->exec("SET time_zone = '+05:30'");
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Get current timestamp in Asia/Kolkata timezone
     */
    private function getCurrentTimestamp() {
        return date('Y-m-d H:i:s');
    }
    
    /**
     * Insert payment initiation record
     */
    public function insertPaymentRecord($data) {
        // Ensure timestamp fields use current Asia/Kolkata time if not provided
        $currentTime = $this->getCurrentTimestamp();
        
        if (empty($data['initiated_at'])) {
            $data['initiated_at'] = $currentTime;
        }
        if (empty($data['created_at'])) {
            $data['created_at'] = $currentTime;
        }
        if (empty($data['modified_at'])) {
            $data['modified_at'] = $currentTime;
        }
        
        $sql = "INSERT INTO payment_records (
            order_id, merchant_txn_no, amount, currency_code,
            customer_name, customer_email, customer_mobile,
            transaction_type, payment_status, addl_param1, addl_param2,
            initiated_at, return_url, initiate_request, initiate_response, 
            redirect_uri, created_at, modified_at
        ) VALUES (
            :order_id, :merchant_txn_no, :amount, :currency_code,
            :customer_name, :customer_email, :customer_mobile,
            :transaction_type, :payment_status, :addl_param1, :addl_param2,
            :initiated_at, :return_url, :initiate_request, :initiate_response,
            :redirect_uri, :created_at, :modified_at
        )";
        
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($data);
    }
    
    /**
     * Update payment record with callback data
     */
    public function updatePaymentRecord($orderId, $data) {
        $fields = [];
        $params = ['order_id' => $orderId];
        
        // Always update modified_at to current timestamp
        $data['modified_at'] = $this->getCurrentTimestamp();
        
        // Set updated_at if payment status or transaction details are being updated
        if (isset($data['payment_status']) || isset($data['transaction_id']) || isset($data['txn_status'])) {
            $data['updated_at'] = $this->getCurrentTimestamp();
        }
        
        // Set payment_datetime if payment is completed
        if (isset($data['payment_status']) && $data['payment_status'] === 'SUCCESS' && empty($data['payment_datetime'])) {
            $data['payment_datetime'] = $this->getCurrentTimestamp();
        }
        
        foreach ($data as $key => $value) {
            if ($key !== 'order_id') {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE payment_records SET " . implode(', ', $fields) . " WHERE order_id = :order_id";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Get payment record by order ID
     */
    public function getPaymentByOrderId($orderId) {
        $sql = "SELECT * FROM payment_records WHERE order_id = :order_id LIMIT 1";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetch();
    }
    
    /**
     * Get payment record by transaction ID
     */
    public function getPaymentByTxnId($txnId) {
        $sql = "SELECT * FROM payment_records WHERE transaction_id = :txn_id LIMIT 1";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['txn_id' => $txnId]);
        return $stmt->fetch();
    }
    
    /**
     * Get all payment records with optional filters
     */
    public function getPayments($filters = [], $limit = 100, $offset = 0) {
        $sql = "SELECT * FROM payment_records";
        $conditions = [];
        $params = [];
        
        if (!empty($filters['payment_status'])) {
            $conditions[] = "payment_status = :payment_status";
            $params['payment_status'] = $filters['payment_status'];
        }
        
        if (!empty($filters['date_from'])) {
            $conditions[] = "initiated_at >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = "initiated_at <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $sql .= " ORDER BY initiated_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->connection->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
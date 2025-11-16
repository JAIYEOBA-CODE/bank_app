<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'fedbank_wallet');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Function to create tables if they don't exist
function createTables($conn)
{
    // Users table
    $usersTable = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        balance DECIMAL(10, 2) DEFAULT 0.00,
        account_number VARCHAR(20) UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL
    )";

    // Transactions table
    $transactionsTable = "CREATE TABLE IF NOT EXISTS transactions (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        type ENUM('deposit', 'send', 'receive', 'request', 'request_sent') NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        recipient_id INT(11) NULL,
        recipient_email VARCHAR(100) NULL,
        description TEXT,
        status ENUM('completed', 'pending', 'cancelled') DEFAULT 'completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE SET NULL
    )";

    // Budgets table
    $budgetsTable = "CREATE TABLE IF NOT EXISTS budgets (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        target_amount DECIMAL(10, 2) NOT NULL,
        month INT(2) NOT NULL,
        year INT(4) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_month_year (user_id, month, year)
    )";

    // Create database if it doesn't exist
    $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $conn->select_db(DB_NAME);

    // Create tables
    $conn->query($usersTable);
    $conn->query($transactionsTable);
    $conn->query($budgetsTable);
}

// Initialize tables
createTables($conn);
?>
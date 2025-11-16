<?php
require_once __DIR__ . '/../config/database.php';

// Format currency
function formatCurrency($amount)
{
    return '₦' . number_format($amount, 2);
}

// Get user by email
function getUserByEmail($conn, $email)
{
    $stmt = $conn->prepare("SELECT id, name, email, account_number FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get user by account number
function getUserByAccountNumber($conn, $accountNumber)
{
    $stmt = $conn->prepare("SELECT id, name, email, account_number FROM users WHERE account_number = ?");
    $stmt->bind_param("s", $accountNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get user balance
function getUserBalance($conn, $userId)
{
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    return $user ? $user['balance'] : 0;
}

// Update user balance
function updateBalance($conn, $userId, $amount)
{
    $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $stmt->bind_param("di", $amount, $userId);
    return $stmt->execute();
}

// Create transaction record
function createTransaction($conn, $userId, $type, $amount, $recipientId = null, $recipientEmail = null, $description = '', $status = 'completed')
{
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, recipient_id, recipient_email, description, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isdssss", $userId, $type, $amount, $recipientId, $recipientEmail, $description, $status);
    return $stmt->execute();
}

// Get transactions for user
function getUserTransactions($conn, $userId, $type = 'all', $limit = 50)
{
    $query = "SELECT t.*, u.name as recipient_name, u.email as recipient_email 
              FROM transactions t 
              LEFT JOIN users u ON t.recipient_id = u.id 
              WHERE t.user_id = ?";

    if ($type === 'credit') {
        $query .= " AND t.type IN ('deposit', 'receive', 'request')";
    } elseif ($type === 'debit') {
        $query .= " AND t.type IN ('send', 'request_sent')";
    }

    $query .= " ORDER BY t.created_at DESC LIMIT ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// Get monthly spending
function getMonthlySpending($conn, $userId, $month, $year)
{
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total 
                            FROM transactions 
                            WHERE user_id = ? 
                            AND type IN ('send', 'request_sent')
                            AND MONTH(created_at) = ? 
                            AND YEAR(created_at) = ?
                            AND status = 'completed'");
    $stmt->bind_param("iii", $userId, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

// Get budget for month
function getBudget($conn, $userId, $month, $year)
{
    $stmt = $conn->prepare("SELECT * FROM budgets WHERE user_id = ? AND month = ? AND year = ?");
    $stmt->bind_param("iii", $userId, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Set or update budget
function setBudget($conn, $userId, $targetAmount, $month, $year)
{
    $stmt = $conn->prepare("INSERT INTO budgets (user_id, target_amount, month, year) 
                            VALUES (?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE target_amount = ?");
    $stmt->bind_param("idiii", $userId, $targetAmount, $month, $year, $targetAmount);
    return $stmt->execute();
}
?>
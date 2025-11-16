<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Get current user data
function getCurrentUser($conn)
{
    if (!isLoggedIn()) {
        return null;
    }

    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, name, email, balance, account_number, last_login FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Register new user
function registerUser($conn, $name, $email, $password)
{
    // Validate input
    if (empty($name) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'All fields are required'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters'];
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already registered'];
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Generate account number
    $accountNumber = '302' . str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);

    // Check if account number exists (unlikely but check anyway)
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE account_number = ?");
    $checkStmt->bind_param("s", $accountNumber);
    $checkStmt->execute();
    while ($checkStmt->get_result()->num_rows > 0) {
        $accountNumber = '302' . str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
        $checkStmt->bind_param("s", $accountNumber);
        $checkStmt->execute();
    }

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, account_number) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $accountNumber);

    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Registration successful'];
    } else {
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

// Login user
function loginUser($conn, $email, $password)
{
    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Email and password are required'];
    }

    $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        // Update last login
        $updateStmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $updateStmt->bind_param("i", $user['id']);
        $updateStmt->execute();

        return ['success' => true, 'message' => 'Login successful'];
    } else {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
}

// Logout user
function logoutUser()
{
    session_unset();
    session_destroy();
    return ['success' => true, 'message' => 'Logged out successfully'];
}

// Require login (redirect if not logged in)
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: /bank_app/index.php');
        exit();
    }
}
?>
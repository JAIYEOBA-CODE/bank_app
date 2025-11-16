<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user = getCurrentUser($conn);
$message = '';
$recipient = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    $recipientInput = trim($_POST['recipient']);
    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description']);
    
    // Find recipient by email or account number
    if (filter_var($recipientInput, FILTER_VALIDATE_EMAIL)) {
        $recipient = getUserByEmail($conn, $recipientInput);
    } else {
        $recipient = getUserByAccountNumber($conn, $recipientInput);
    }
    
    if (!$recipient) {
        $message = '<div class="alert alert-danger">Recipient not found. Please check the email or account number.</div>';
    } elseif ($recipient['id'] == $user['id']) {
        $message = '<div class="alert alert-danger">You cannot send money to yourself.</div>';
    } elseif ($amount <= 0) {
        $message = '<div class="alert alert-danger">Amount must be greater than zero</div>';
    } elseif ($amount > $user['balance']) {
        $message = '<div class="alert alert-danger">Insufficient balance. Your current balance is ' . formatCurrency($user['balance']) . '</div>';
    } else {
        // Check budget limit
        $currentMonth = date('m');
        $currentYear = date('Y');
        $budget = getBudget($conn, $user['id'], $currentMonth, $currentYear);
        if ($budget) {
            $monthlySpending = getMonthlySpending($conn, $user['id'], $currentMonth, $currentYear);
            if (($monthlySpending + $amount) > $budget['target_amount']) {
                $remaining = $budget['target_amount'] - $monthlySpending;
                $message = '<div class="alert alert-danger">This transaction would exceed your monthly budget of ' . formatCurrency($budget['target_amount']) . '. You can only spend ' . formatCurrency($remaining) . ' more this month.</div>';
            } else {
                // Proceed with transaction
                $conn->begin_transaction();
                
                try {
                    // Deduct from sender
                    if (updateBalance($conn, $user['id'], -$amount)) {
                        // Add to recipient
                        if (updateBalance($conn, $recipient['id'], $amount)) {
                            // Create transaction records
                            createTransaction($conn, $user['id'], 'send', $amount, $recipient['id'], $recipient['email'], $description ?: 'Money transfer');
                            createTransaction($conn, $recipient['id'], 'receive', $amount, $user['id'], $user['email'], $description ?: 'Money received');
                            
                            $conn->commit();
                            $message = '<div class="alert alert-success">Money sent successfully to ' . htmlspecialchars($recipient['name']) . '!</div>';
                            
                            // Refresh user data
                            $user = getCurrentUser($conn);
                            $recipient = null; // Clear form
                        } else {
                            throw new Exception('Failed to update recipient balance');
                        }
                    } else {
                        throw new Exception('Failed to update sender balance');
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = '<div class="alert alert-danger">Transaction failed. Please try again.</div>';
                }
            }
        } else {
            // No budget set, proceed normally
            $conn->begin_transaction();
            
            try {
                // Deduct from sender
                if (updateBalance($conn, $user['id'], -$amount)) {
                    // Add to recipient
                    if (updateBalance($conn, $recipient['id'], $amount)) {
                        // Create transaction records
                        createTransaction($conn, $user['id'], 'send', $amount, $recipient['id'], $recipient['email'], $description ?: 'Money transfer');
                        createTransaction($conn, $recipient['id'], 'receive', $amount, $user['id'], $user['email'], $description ?: 'Money received');
                        
                        $conn->commit();
                        $message = '<div class="alert alert-success">Money sent successfully to ' . htmlspecialchars($recipient['name']) . '!</div>';
                        
                        // Refresh user data
                        $user = getCurrentUser($conn);
                        $recipient = null; // Clear form
                    } else {
                        throw new Exception('Failed to update recipient balance');
                    }
                } else {
                    throw new Exception('Failed to update sender balance');
                }
            } catch (Exception $e) {
                $conn->rollback();
                $message = '<div class="alert alert-danger">Transaction failed. Please try again.</div>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Money - FedBank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard">
    <div class="sidebar">
        <div class="text-center mb-4 p-3">
            <h4 class="fw-bold"><i class="bi bi-bank"></i> FedBank</h4>
        </div>
        <a href="dashboard.php" class="sidebar-item">
            <i class="bi bi-house-door"></i>
            <span>Home</span>
        </a>
        <a href="deposit.php" class="sidebar-item">
            <i class="bi bi-wallet2"></i>
            <span>Deposit</span>
        </a>
        <a href="send_money.php" class="sidebar-item active">
            <i class="bi bi-send"></i>
            <span>Send Money</span>
        </a>
        <a href="request_money.php" class="sidebar-item">
            <i class="bi bi-envelope-paper"></i>
            <span>Request Money</span>
        </a>
        <a href="transactions.php" class="sidebar-item">
            <i class="bi bi-clock-history"></i>
            <span>Transactions</span>
        </a>
        <a href="budget.php" class="sidebar-item">
            <i class="bi bi-piggy-bank"></i>
            <span>Budget</span>
        </a>
        <a href="includes/logout.php" class="sidebar-item">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>

    <div class="main-content">
        <div class="header">
            <h3>Send Money</h3>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="stat-card">
                    <h5 class="mb-4">Transfer Funds</h5>
                    <?php echo $message; ?>
                    
                    <div class="mb-4 p-3 bg-light rounded">
                        <p class="text-muted mb-1">Available Balance</p>
                        <h3 class="mb-0"><?php echo formatCurrency($user['balance']); ?></h3>
                    </div>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="recipient" class="form-label">Recipient Email or Account Number</label>
                            <input type="text" class="form-control form-control-lg" id="recipient" name="recipient" 
                                   value="<?php echo $recipient ? htmlspecialchars($recipient['email']) : ''; ?>" required>
                            <small class="text-muted">Enter recipient's email address or account number</small>
                        </div>
                        
                        <?php if ($recipient && $recipient['id'] != $user['id']): ?>
                        <div class="alert alert-info">
                            <strong>Recipient:</strong> <?php echo htmlspecialchars($recipient['name']); ?><br>
                            <small>Account: <?php echo htmlspecialchars($recipient['account_number']); ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (₦)</label>
                            <input type="number" class="form-control form-control-lg" id="amount" name="amount" 
                                   step="0.01" min="0.01" max="<?php echo $user['balance']; ?>" required data-currency>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <input type="text" class="form-control" id="description" name="description" 
                                   placeholder="e.g., Payment for services">
                        </div>
                        
                        <button type="submit" name="send" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-send"></i> Send Money
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <button class="btn btn-primary d-md-none position-fixed bottom-0 end-0 m-3 rounded-circle" style="width: 50px; height: 50px; z-index: 999;" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user = getCurrentUser($conn);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deposit'])) {
    $amount = floatval($_POST['amount']);
    
    if ($amount <= 0) {
        $message = '<div class="alert alert-danger">Amount must be greater than zero</div>';
    } elseif ($amount > 1000000) {
        $message = '<div class="alert alert-danger">Maximum deposit amount is ₦1,000,000</div>';
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update balance
            if (updateBalance($conn, $user['id'], $amount)) {
                // Create transaction record
                createTransaction($conn, $user['id'], 'deposit', $amount, null, null, 'Virtual deposit');
                
                $conn->commit();
                $message = '<div class="alert alert-success">Deposit successful! Your balance has been updated.</div>';
                
                // Refresh user data
                $user = getCurrentUser($conn);
            } else {
                throw new Exception('Failed to update balance');
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = '<div class="alert alert-danger">Deposit failed. Please try again.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit Money - FedBank</title>
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
        <a href="deposit.php" class="sidebar-item active">
            <i class="bi bi-wallet2"></i>
            <span>Deposit</span>
        </a>
        <a href="send_money.php" class="sidebar-item">
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
            <h3>Deposit Money</h3>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="stat-card">
                    <h5 class="mb-4">Virtual Deposit</h5>
                    <?php echo $message; ?>
                    
                    <div class="mb-4 p-3 bg-light rounded">
                        <p class="text-muted mb-1">Current Balance</p>
                        <h3 class="mb-0"><?php echo formatCurrency($user['balance']); ?></h3>
                    </div>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Deposit Amount (₦)</label>
                            <input type="number" class="form-control form-control-lg" id="amount" name="amount" 
                                   step="0.01" min="0.01" max="1000000" required data-currency>
                            <small class="text-muted">Minimum: ₦0.01 | Maximum: ₦1,000,000</small>
                        </div>
                        <button type="submit" name="deposit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-plus-circle"></i> Deposit Money
                        </button>
                    </form>

                    <div class="mt-4 p-3 bg-info bg-opacity-10 rounded">
                        <p class="mb-0"><i class="bi bi-info-circle"></i> <strong>Note:</strong> This is a virtual deposit system. No actual payment gateway is integrated.</p>
                    </div>
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


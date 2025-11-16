<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user = getCurrentUser($conn);
$message = '';

// Get current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Handle budget setting/updating
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_budget'])) {
    $targetAmount = floatval($_POST['target_amount']);
    
    if ($targetAmount <= 0) {
        $message = '<div class="alert alert-danger">Target amount must be greater than zero</div>';
    } else {
        if (setBudget($conn, $user['id'], $targetAmount, $currentMonth, $currentYear)) {
            $message = '<div class="alert alert-success">Budget set successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Failed to set budget. Please try again.</div>';
        }
    }
}

// Get current budget
$budget = getBudget($conn, $user['id'], $currentMonth, $currentYear);
$monthlySpending = getMonthlySpending($conn, $user['id'], $currentMonth, $currentYear);
$remaining = $budget ? ($budget['target_amount'] - $monthlySpending) : 0;
$budgetProgress = $budget ? ($monthlySpending / $budget['target_amount'] * 100) : 0;
if ($budgetProgress > 100) $budgetProgress = 100;

// Get month name
$monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
               'July', 'August', 'September', 'October', 'November', 'December'];
$monthName = $monthNames[intval($currentMonth)];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Management - FedBank</title>
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
        <a href="budget.php" class="sidebar-item active">
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
            <h3>Monthly Budget Management</h3>
            <p class="text-muted mb-0"><?php echo $monthName . ' ' . $currentYear; ?></p>
        </div>

        <?php echo $message; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="stat-card">
                    <h5 class="mb-4">Set Monthly Budget</h5>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="target_amount" class="form-label">Monthly Spending Target (₦)</label>
                            <input type="number" class="form-control form-control-lg" id="target_amount" name="target_amount" 
                                   step="0.01" min="0.01" value="<?php echo $budget ? $budget['target_amount'] : ''; ?>" required data-currency>
                            <small class="text-muted">Set how much you want to spend this month</small>
                        </div>
                        
                        <button type="submit" name="set_budget" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-piggy-bank"></i> <?php echo $budget ? 'Update' : 'Set'; ?> Budget
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="col-md-6">
                <?php if ($budget): ?>
                <div class="stat-card">
                    <h5 class="mb-4">Budget Progress</h5>
                    
                    <div class="text-center mb-4">
                        <div class="budget-circle mx-auto mb-3" style="--angle: <?php echo $budgetProgress * 3.6; ?>deg">
                            <div class="budget-circle-content">
                                <h4 class="mb-0"><?php echo number_format($budgetProgress, 1); ?>%</h4>
                                <small class="text-muted">Used</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <p class="text-muted mb-1">Target</p>
                            <h5 class="mb-0"><?php echo formatCurrency($budget['target_amount']); ?></h5>
                        </div>
                        <div class="col-6">
                            <p class="text-muted mb-1">Spent</p>
                            <h5 class="mb-0 text-<?php echo $budgetProgress > 100 ? 'danger' : ($budgetProgress > 80 ? 'warning' : 'success'); ?>">
                                <?php echo formatCurrency($monthlySpending); ?>
                            </h5>
                        </div>
                    </div>
                    
                    <div class="progress-bar mb-3">
                        <div class="progress-bar-fill" style="width: <?php echo $budgetProgress; ?>%"></div>
                    </div>
                    
                    <div class="p-3 rounded <?php echo $remaining < 0 ? 'bg-danger bg-opacity-10' : ($remaining < ($budget['target_amount'] * 0.2) ? 'bg-warning bg-opacity-10' : 'bg-success bg-opacity-10'); ?>">
                        <p class="mb-0">
                            <strong><?php echo $remaining >= 0 ? 'Remaining:' : 'Over budget by:'; ?></strong>
                            <span class="fw-bold"><?php echo formatCurrency(abs($remaining)); ?></span>
                        </p>
                    </div>
                </div>
                <?php else: ?>
                <div class="stat-card">
                    <h5 class="mb-4">No Budget Set</h5>
                    <p class="text-muted">Set a monthly spending target to start tracking your budget.</p>
                    <div class="text-center">
                        <i class="bi bi-piggy-bank display-1 text-muted"></i>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($budget): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="stat-card">
                    <h5 class="mb-4">Budget Breakdown</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="p-3 bg-light rounded">
                                <p class="text-muted mb-1">Monthly Target</p>
                                <h4 class="mb-0"><?php echo formatCurrency($budget['target_amount']); ?></h4>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="p-3 bg-light rounded">
                                <p class="text-muted mb-1">Amount Spent</p>
                                <h4 class="mb-0 text-<?php echo $budgetProgress > 100 ? 'danger' : ($budgetProgress > 80 ? 'warning' : 'success'); ?>">
                                    <?php echo formatCurrency($monthlySpending); ?>
                                </h4>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="p-3 bg-light rounded">
                                <p class="text-muted mb-1">Remaining Budget</p>
                                <h4 class="mb-0 <?php echo $remaining < 0 ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo formatCurrency($remaining); ?>
                                </h4>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <p class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            This budget tracks all outgoing transactions (sends and money requests) for <?php echo $monthName . ' ' . $currentYear; ?>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <button class="btn btn-primary d-md-none position-fixed bottom-0 end-0 m-3 rounded-circle" style="width: 50px; height: 50px; z-index: 999;" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>


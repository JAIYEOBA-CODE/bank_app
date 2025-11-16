<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user = getCurrentUser($conn);
$userId = $_SESSION['user_id'];

// Get recent transactions
$recentTransactions = getUserTransactions($conn, $userId, 'all', 5);

// Get current month budget
$currentMonth = date('m');
$currentYear = date('Y');
$budget = getBudget($conn, $userId, $currentMonth, $currentYear);
$monthlySpending = getMonthlySpending($conn, $userId, $currentMonth, $currentYear);
$budgetProgress = $budget ? ($monthlySpending / $budget['target_amount'] * 100) : 0;
if ($budgetProgress > 100) $budgetProgress = 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FedBank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center mb-4 p-3">
            <h4 class="fw-bold"><i class="bi bi-bank"></i> FedBank</h4>
        </div>
        <a href="dashboard.php" class="sidebar-item active">
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
        <a href="budget.php" class="sidebar-item">
            <i class="bi bi-piggy-bank"></i>
            <span>Budget</span>
        </a>
        <a href="includes/logout.php" class="sidebar-item">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="mb-1">Hello, <?php echo htmlspecialchars($user['name']); ?>!</h3>
                <p class="text-muted mb-0">
                    Last login: <?php echo $user['last_login'] ? date('d.m.y \a\t g:i A', strtotime($user['last_login'])) : 'First login'; ?>
                </p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="input-group" style="max-width: 300px;">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" placeholder="Search something...">
                </div>
                <div class="dropdown">
                    <button class="btn btn-light rounded-circle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle" style="font-size: 1.5rem;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="includes/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Balance Card -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="balance-card">
                    <h5 class="mb-3">Available Balance</h5>
                    <p class="mb-2 text-white-50">Account # <?php echo htmlspecialchars($user['account_number']); ?></p>
                    <h1 class="display-4 fw-bold balance-amount" data-balance="<?php echo $user['balance']; ?>">
                        <?php echo formatCurrency($user['balance']); ?>
                    </h1>
                    <div class="mt-4">
                        <a href="transactions.php" class="btn btn-light">View Statement</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card h-100">
                    <h5 class="mb-3">Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="deposit.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Deposit Money
                        </a>
                        <a href="send_money.php" class="btn btn-outline-primary">
                            <i class="bi bi-send"></i> Send Money
                        </a>
                        <a href="request_money.php" class="btn btn-outline-primary">
                            <i class="bi bi-envelope-paper"></i> Request Money
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Budget Progress -->
        <?php if ($budget): ?>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <h5 class="mb-3">Monthly Budget Progress</h5>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <p class="text-muted mb-1">Spent this month</p>
                            <h4 class="mb-0"><?php echo formatCurrency($monthlySpending); ?></h4>
                        </div>
                        <div class="text-end">
                            <p class="text-muted mb-1">Target</p>
                            <h4 class="mb-0"><?php echo formatCurrency($budget['target_amount']); ?></h4>
                        </div>
                    </div>
                    <div class="progress-bar mb-2">
                        <div class="progress-bar-fill" style="width: <?php echo $budgetProgress; ?>%"></div>
                    </div>
                    <p class="text-muted mb-0">
                        <?php echo number_format($budgetProgress, 1); ?>% of budget used
                    </p>
                    <a href="budget.php" class="btn btn-sm btn-primary mt-3">Manage Budget</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <h5 class="mb-3">Savings Goal</h5>
                    <div class="text-center">
                        <div class="budget-circle mx-auto mb-3" style="--angle: <?php echo ($user['balance'] / 10000 * 360); ?>deg">
                            <div class="budget-circle-content">
                                <h4 class="mb-0"><?php echo formatCurrency($user['balance']); ?></h4>
                                <small class="text-muted">Current Balance</small>
                            </div>
                        </div>
                        <p class="text-muted">Target: ₦10,000</p>
                        <p class="mb-0"><?php echo number_format(($user['balance'] / 10000 * 100), 1); ?>% of goal achieved</p>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="stat-card">
                    <h5 class="mb-3">Monthly Budget</h5>
                    <p class="text-muted">You haven't set a budget for this month yet.</p>
                    <a href="budget.php" class="btn btn-primary">Set Budget</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Transactions -->
        <div class="row">
            <div class="col-12">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Recent Transactions</h5>
                        <a href="transactions.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <?php if ($recentTransactions->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($transaction = $recentTransactions->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $typeLabels = [
                                            'deposit' => '<span class="badge bg-success">Deposit</span>',
                                            'send' => '<span class="badge bg-danger">Sent</span>',
                                            'receive' => '<span class="badge bg-success">Received</span>',
                                            'request' => '<span class="badge bg-info">Request</span>',
                                            'request_sent' => '<span class="badge bg-warning">Request Sent</span>'
                                        ];
                                        echo $typeLabels[$transaction['type']] ?? $transaction['type'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($transaction['recipient_name']) {
                                            echo htmlspecialchars($transaction['recipient_name']);
                                        } else {
                                            echo htmlspecialchars($transaction['description'] ?: ucfirst($transaction['type']));
                                        }
                                        ?>
                                    </td>
                                    <td class="<?php echo in_array($transaction['type'], ['deposit', 'receive', 'request']) ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo in_array($transaction['type'], ['deposit', 'receive', 'request']) ? '+' : '-'; ?>
                                        <?php echo formatCurrency($transaction['amount']); ?>
                                    </td>
                                    <td><?php echo date('d.m.y', strtotime($transaction['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $transaction['status'] === 'completed' ? 'success' : ($transaction['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($transaction['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted text-center py-4">No transactions yet. Start by making a deposit!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Toggle -->
    <button class="btn btn-primary d-md-none position-fixed bottom-0 end-0 m-3 rounded-circle" style="width: 50px; height: 50px; z-index: 999;" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>


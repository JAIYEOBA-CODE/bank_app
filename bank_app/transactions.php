<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user = getCurrentUser($conn);
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Get transactions based on filter
$transactions = getUserTransactions($conn, $user['id'], $filter, 100);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - FedBank</title>
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
        <a href="transactions.php" class="sidebar-item active">
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
        <div class="header d-flex justify-content-between align-items-center flex-wrap">
            <h3>Transaction History</h3>
            <div class="btn-group" role="group">
                <a href="?filter=all" class="btn btn-<?php echo $filter === 'all' ? 'primary' : 'outline-primary'; ?>">
                    All
                </a>
                <a href="?filter=credit"
                    class="btn btn-<?php echo $filter === 'credit' ? 'primary' : 'outline-primary'; ?>">
                    Credit
                </a>
                <a href="?filter=debit"
                    class="btn btn-<?php echo $filter === 'debit' ? 'primary' : 'outline-primary'; ?>">
                    Debit
                </a>
            </div>
        </div>

        <div class="stat-card">
            <?php if ($transactions->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Recipient/Sender</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($transaction = $transactions->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php echo date('d.m.y', strtotime($transaction['created_at'])); ?><br>
                                        <small
                                            class="text-muted"><?php echo date('g:i A', strtotime($transaction['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $typeLabels = [
                                            'deposit' => '<span class="badge bg-success"><i class="bi bi-arrow-down-circle"></i> Deposit</span>',
                                            'send' => '<span class="badge bg-danger"><i class="bi bi-arrow-up-circle"></i> Sent</span>',
                                            'receive' => '<span class="badge bg-success"><i class="bi bi-arrow-down-circle"></i> Received</span>',
                                            'request' => '<span class="badge bg-info"><i class="bi bi-envelope-paper"></i> Request</span>',
                                            'request_sent' => '<span class="badge bg-warning"><i class="bi bi-envelope-paper-fill"></i> Request Sent</span>'
                                        ];
                                        echo $typeLabels[$transaction['type']] ?? $transaction['type'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($transaction['description']) {
                                            echo htmlspecialchars($transaction['description']);
                                        } else {
                                            echo ucfirst(str_replace('_', ' ', $transaction['type']));
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($transaction['recipient_name']) {
                                            echo htmlspecialchars($transaction['recipient_name']);
                                            if ($transaction['recipient_email']) {
                                                echo '<br><small class="text-muted">' . htmlspecialchars($transaction['recipient_email']) . '</small>';
                                            }
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-end">
                                        <span
                                            class="fw-bold <?php echo in_array($transaction['type'], ['deposit', 'receive', 'request']) ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo in_array($transaction['type'], ['deposit', 'receive', 'request']) ? '+' : '-'; ?>
                                            <?php echo formatCurrency($transaction['amount']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span
                                            class="badge bg-<?php echo $transaction['status'] === 'completed' ? 'success' : ($transaction['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($transaction['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="text-muted mt-3">No transactions found</p>
                    <a href="deposit.php" class="btn btn-primary">Make Your First Deposit</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <button class="btn btn-primary d-md-none position-fixed bottom-0 end-0 m-3 rounded-circle"
        style="width: 50px; height: 50px; z-index: 999;" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>

</html>
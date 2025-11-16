<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user = getCurrentUser($conn);
$message = '';
$recipient = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request'])) {
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
        $message = '<div class="alert alert-danger">You cannot request money from yourself.</div>';
    } elseif ($amount <= 0) {
        $message = '<div class="alert alert-danger">Amount must be greater than zero</div>';
    } else {
        // Create money request transaction
        if (createTransaction($conn, $user['id'], 'request', $amount, $recipient['id'], $recipient['email'], $description ?: 'Money request', 'pending')) {
            createTransaction($conn, $recipient['id'], 'request_sent', $amount, $user['id'], $user['email'], $description ?: 'Money request from ' . $user['name'], 'pending');
            $message = '<div class="alert alert-success">Money request sent to ' . htmlspecialchars($recipient['name']) . '!</div>';
            $recipient = null; // Clear form
        } else {
            $message = '<div class="alert alert-danger">Failed to send request. Please try again.</div>';
        }
    }
}

// Handle request approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_request'])) {
    $requestId = intval($_POST['request_id']);
    $amount = floatval($_POST['request_amount']);
    $senderId = intval($_POST['sender_id']);

    if ($amount <= 0) {
        $message = '<div class="alert alert-danger">Invalid amount</div>';
    } elseif ($amount > $user['balance']) {
        $message = '<div class="alert alert-danger">Insufficient balance</div>';
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
                    // Get current balances before transaction
                    $approverBalanceBefore = floatval($user['balance']);
                    $requesterBalanceBefore = getUserBalance($conn, $senderId);

                    // Deduct from approver (person who approved) - use direct SQL to ensure it works
                    $stmtDeduct = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                    $stmtDeduct->bind_param("di", $amount, $user['id']);
                    if (!$stmtDeduct->execute()) {
                        throw new Exception('Failed to deduct from approver balance');
                    }
                    $stmtDeduct->close();

                    // Add to requester (person who requested) - use direct SQL to ensure it works
                    $stmtAdd = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                    $stmtAdd->bind_param("di", $amount, $senderId);
                    if (!$stmtAdd->execute()) {
                        throw new Exception('Failed to add to requester balance');
                    }
                    $stmtAdd->close();

                    // Verify balances were updated
                    $approverBalanceAfter = getUserBalance($conn, $user['id']);
                    $requesterBalanceAfter = getUserBalance($conn, $senderId);

                    // Update both request transaction records to completed
                    $stmt = $conn->prepare("UPDATE transactions SET status = 'completed' WHERE id = ? OR (user_id = ? AND recipient_id = ? AND type = 'request' AND status = 'pending')");
                    $stmt->bind_param("iii", $requestId, $senderId, $user['id']);
                    $stmt->execute();
                    $stmt->close();

                    // Also update the request_sent transaction
                    $stmt2 = $conn->prepare("UPDATE transactions SET status = 'completed' WHERE user_id = ? AND recipient_id = ? AND type = 'request_sent' AND status = 'pending'");
                    $stmt2->bind_param("ii", $senderId, $user['id']);
                    $stmt2->execute();
                    $stmt2->close();

                    // Create completed transaction records
                    createTransaction($conn, $user['id'], 'send', $amount, $senderId, null, 'Approved money request', 'completed');
                    createTransaction($conn, $senderId, 'receive', $amount, $user['id'], null, 'Money request approved', 'completed');

                    $conn->commit();

                    // Refresh user data to show updated balance
                    $user = getCurrentUser($conn);

                    $message = '<div class="alert alert-success">Money request approved! ' . formatCurrency($amount) . ' has been deducted from your account and added to the requester.<br>Your balance: ' . formatCurrency($approverBalanceBefore) . ' → ' . formatCurrency($approverBalanceAfter) . '</div>';
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = '<div class="alert alert-danger">Failed to approve request: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
        } else {
            // No budget set, proceed normally
            $conn->begin_transaction();

            try {
                // Get current balances before transaction
                $approverBalanceBefore = floatval($user['balance']);
                $requesterBalanceBefore = getUserBalance($conn, $senderId);

                // Deduct from approver (person who approved) - use direct SQL to ensure it works
                $stmtDeduct = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $stmtDeduct->bind_param("di", $amount, $user['id']);
                if (!$stmtDeduct->execute()) {
                    throw new Exception('Failed to deduct from approver balance');
                }
                $stmtDeduct->close();

                // Add to requester (person who requested) - use direct SQL to ensure it works
                $stmtAdd = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmtAdd->bind_param("di", $amount, $senderId);
                if (!$stmtAdd->execute()) {
                    throw new Exception('Failed to add to requester balance');
                }
                $stmtAdd->close();

                // Verify balances were updated
                $approverBalanceAfter = getUserBalance($conn, $user['id']);
                $requesterBalanceAfter = getUserBalance($conn, $senderId);

                // Update both request transaction records to completed
                $stmt = $conn->prepare("UPDATE transactions SET status = 'completed' WHERE id = ? OR (user_id = ? AND recipient_id = ? AND type = 'request' AND status = 'pending')");
                $stmt->bind_param("iii", $requestId, $senderId, $user['id']);
                $stmt->execute();
                $stmt->close();

                // Also update the request_sent transaction
                $stmt2 = $conn->prepare("UPDATE transactions SET status = 'completed' WHERE user_id = ? AND recipient_id = ? AND type = 'request_sent' AND status = 'pending'");
                $stmt2->bind_param("ii", $senderId, $user['id']);
                $stmt2->execute();
                $stmt2->close();

                // Create completed transaction records
                createTransaction($conn, $user['id'], 'send', $amount, $senderId, null, 'Approved money request', 'completed');
                createTransaction($conn, $senderId, 'receive', $amount, $user['id'], null, 'Money request approved', 'completed');

                $conn->commit();

                // Refresh user data to show updated balance
                $user = getCurrentUser($conn);

                $message = '<div class="alert alert-success">Money request approved! ' . formatCurrency($amount) . ' has been deducted from your account and added to the requester.<br>Your balance: ' . formatCurrency($approverBalanceBefore) . ' → ' . formatCurrency($approverBalanceAfter) . '</div>';
            } catch (Exception $e) {
                $conn->rollback();
                $message = '<div class="alert alert-danger">Failed to approve request: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
}

// Get pending requests for current user (requests sent TO current user)
$stmt = $conn->prepare("SELECT t.*, u.name as sender_name, u.email as sender_email 
                        FROM transactions t 
                        JOIN users u ON t.user_id = u.id 
                        WHERE t.recipient_id = ? 
                        AND t.type = 'request' 
                        AND t.status = 'pending' 
                        ORDER BY t.created_at DESC");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$pendingRequests = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Money - FedBank</title>
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
        <a href="request_money.php" class="sidebar-item active">
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
            <h3>Request Money</h3>
        </div>

        <?php echo $message; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="stat-card">
                    <h5 class="mb-4">Send Money Request</h5>

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
                            <label for="amount" class="form-label">Request Amount (₦)</label>
                            <input type="number" class="form-control form-control-lg" id="amount" name="amount"
                                step="0.01" min="0.01" required data-currency>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <input type="text" class="form-control" id="description" name="description"
                                placeholder="e.g., Payment for services">
                        </div>

                        <button type="submit" name="request" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-envelope-paper"></i> Send Request
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-6">
                <div class="stat-card">
                    <h5 class="mb-4">Pending Requests</h5>

                    <?php if ($pendingRequests->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while ($request = $pendingRequests->fetch_assoc()): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($request['sender_name']); ?></h6>
                                    <small
                                        class="text-muted"><?php echo htmlspecialchars($request['sender_email']); ?></small>
                                </div>
                                <span class="badge bg-warning">Pending</span>
                            </div>
                            <p class="mb-2"><strong><?php echo formatCurrency($request['amount']); ?></strong></p>
                            <?php if ($request['description']): ?>
                            <p class="text-muted small mb-2"><?php echo htmlspecialchars($request['description']); ?>
                            </p>
                            <?php endif; ?>
                            <small
                                class="text-muted"><?php echo date('d.m.y \a\t g:i A', strtotime($request['created_at'])); ?></small>
                            <form method="POST" class="mt-2">
                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                <input type="hidden" name="request_amount" value="<?php echo $request['amount']; ?>">
                                <input type="hidden" name="sender_id" value="<?php echo $request['user_id']; ?>">
                                <button type="submit" name="approve_request" class="btn btn-sm btn-success">
                                    <i class="bi bi-check-circle"></i> Approve
                                </button>
                            </form>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted text-center py-4">No pending requests</p>
                    <?php endif; ?>
                </div>
            </div>
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
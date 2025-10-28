<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
include 'config.php';

// Handle withdrawal approval
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_withdrawal'])) {
    $transaction_id = intval($_POST['transaction_id']);
    $stmt = $conn->prepare("UPDATE transactions SET status = 'approved' WHERE id = ? AND type = 'withdraw'");
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();
    $stmt->close();

    // Reset withdrawing amount for user
    $stmt = $conn->prepare("SELECT user_id, amount FROM transactions WHERE id = ?");
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();
    $trans = $stmt->get_result()->fetch_assoc();
    $conn->query("UPDATE users SET withdrawing = withdrawing - {$trans['amount']} WHERE id = {$trans['user_id']}");
    $stmt->close();
    header("Location: admin.php");
    exit();
}

// Handle deposit approval
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_deposit'])) {
    $transaction_id = intval($_POST['transaction_id']);
    $stmt = $conn->prepare("UPDATE transactions SET status = 'approved' WHERE id = ? AND type = 'deposit'");
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();
    $stmt->close();

    // Add deposit amount to user's balance
    $stmt = $conn->prepare("SELECT user_id, amount FROM transactions WHERE id = ?");
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();
    $trans = $stmt->get_result()->fetch_assoc();
    $conn->query("UPDATE users SET balance = balance + {$trans['amount']} WHERE id = {$trans['user_id']}");
    $stmt->close();
    header("Location: admin.php");
    exit();
}

// Automatic payouts are now handled by auto_payout.php script running daily via cron job

// Get pending withdrawals
$pending_withdrawals = $conn->query("SELECT t.*, u.username FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.type = 'withdraw' AND t.status = 'pending'");

// Get pending deposits
$pending_deposits = $conn->query("SELECT t.*, u.username FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.type = 'deposit' AND t.status = 'pending'");

// Get all users with optional search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT * FROM users";
if ($search) {
    $query .= " WHERE username LIKE '%$search%' OR phone LIKE '%$search%'";
}
$query .= " ORDER BY created_at DESC";
$users = $conn->query($query);

// Function to fetch Binance transactions (deposits and withdrawals)
function getBinanceTransactions() {
    $api_key = BINANCE_API_KEY;
    $secret_key = BINANCE_SECRET_KEY;
    $timestamp = round(microtime(true) * 1000);
    $query_string = "timestamp=$timestamp";
    $signature = hash_hmac('sha256', $query_string, $secret_key);

    $transactions = [];

    // Fetch deposits
    $deposit_url = "https://api.binance.com/sapi/v1/capital/deposit/hisrec?{$query_string}&signature={$signature}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $deposit_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-MBX-APIKEY: $api_key"
    ]);
    $deposit_response = curl_exec($ch);
    curl_close($ch);
    $deposits = json_decode($deposit_response, true);
    if (is_array($deposits)) {
        foreach ($deposits as $dep) {
            if (is_array($dep)) {
                $transactions[] = [
                    'type' => 'deposit',
                    'asset' => $dep['coin'] ?? '',
                    'amount' => $dep['amount'] ?? '',
                    'address' => $dep['address'] ?? '',
                    'txId' => $dep['txId'] ?? '',
                    'status' => $dep['status'] ?? '',
                    'insertTime' => $dep['insertTime'] ?? 0
                ];
            }
        }
    }

    // Fetch withdrawals
    $withdraw_url = "https://api.binance.com/sapi/v1/capital/withdraw/history?{$query_string}&signature={$signature}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $withdraw_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-MBX-APIKEY: $api_key"
    ]);
    $withdraw_response = curl_exec($ch);
    curl_close($ch);
    $withdrawals = json_decode($withdraw_response, true);
    if (is_array($withdrawals)) {
        foreach ($withdrawals as $wd) {
            if (is_array($wd)) {
                $transactions[] = [
                    'type' => 'withdraw',
                    'asset' => $wd['coin'] ?? '',
                    'amount' => $wd['amount'] ?? '',
                    'address' => $wd['address'] ?? '',
                    'txId' => $wd['txId'] ?? '',
                    'status' => $wd['status'] ?? '',
                    'insertTime' => $wd['applyTime'] ?? 0
                ];
            }
        }
    }

    // Sort by insertTime descending
    usort($transactions, function($a, $b) {
        return $b['insertTime'] <=> $a['insertTime'];
    });

    return $transactions;
}

$binance_transactions = getBinanceTransactions();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="icon" href="p.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar">
        <div class="container">
            <a class="navbar-brand" href="#"><img src="p.png" alt="Profit Pulse" style="height: 40px;"> Profit Pulse Admin</a>
            <div class="d-flex">
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2>Admin Dashboard</h2>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Pending Withdrawals</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Amount</th>
                                    <th>Wallet</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($wd = $pending_withdrawals->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $wd['username']; ?></td>
                                        <td>$<?php echo number_format($wd['amount'], 2); ?></td>
                                        <td><?php echo $wd['wallet_address']; ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="transaction_id" value="<?php echo $wd['id']; ?>">
                                                <button type="submit" name="approve_withdrawal" class="btn btn-success btn-sm">Approve</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Pending Deposits</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($dp = $pending_deposits->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $dp['username']; ?></td>
                                        <td>$<?php echo number_format($dp['amount'], 2); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="transaction_id" value="<?php echo $dp['id']; ?>">
                                                <button type="submit" name="approve_deposit" class="btn btn-success btn-sm">Approve</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>



        <div class="card mt-4">
            <div class="card-header">
                <h5>Binance Transactions</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Asset</th>
                            <th>Amount</th>
                            <th>Address</th>
                            <th>Tx ID</th>
                            <th>Status</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($binance_transactions as $transaction): ?>
                            <tr>
                                <td><?php echo ucfirst($transaction['type']); ?></td>
                                <td><?php echo $transaction['asset']; ?></td>
                                <td><?php echo $transaction['amount']; ?></td>
                                <td><?php echo $transaction['address']; ?></td>
                                <td><?php echo $transaction['txId']; ?></td>
                                <td><?php echo $transaction['status']; ?></td>
                                <td><?php echo date('Y-m-d H:i:s', $transaction['insertTime'] / 1000); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5>All Users</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search by username or phone" value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if ($search): ?>
                            <a href="admin.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Phone</th>
                            <th>Balance</th>
                            <th>Withdrawing</th>
                            <th>Role</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo $user['username']; ?></td>
                                <td><?php echo $user['phone']; ?></td>
                                <td>$<?php echo number_format($user['balance'], 2); ?></td>
                                <td>$<?php echo number_format($user['withdrawing'], 2); ?></td>
                                <td><?php echo $user['role']; ?></td>
                                <td><?php echo $user['created_at']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>

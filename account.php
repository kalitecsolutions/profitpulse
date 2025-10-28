<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'subscriber') {
    header("Location: index.php");
    exit();
}
include 'config.php';
$user_id = $_SESSION['user_id'];

// Get unread message count
$unread_count = 0;
if (isset($_SESSION['last_read_message_id'])) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE id > ?");
    $stmt->bind_param("i", $_SESSION['last_read_message_id']);
    $stmt->execute();
    $unread_count = $stmt->get_result()->fetch_assoc()['count'];
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();



$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $profile_pic = $user['profile_pic'];
    $withdraw_wallet = trim($_POST['withdraw_wallet']);

    // Handle file upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $target_file = $target_dir . basename($_FILES["profile_pic"]["name"]);
        move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file);
        $profile_pic = $target_file;
    }

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, profile_pic = ?, withdraw_wallet = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $username, $hashed_password, $profile_pic, $withdraw_wallet, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, profile_pic = ?, withdraw_wallet = ? WHERE id = ?");
        $stmt->bind_param("sssi", $username, $profile_pic, $withdraw_wallet, $user_id);
    }
    if ($stmt->execute()) {
        $_SESSION['message'] = "Profile updated successfully!";
        $_SESSION['username'] = $username;
    } else {
        $_SESSION['message'] = "Update failed.";
    }
    $stmt->close();
    header("Location: account.php");
    exit();
}

// Handle deposit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deposit'])) {
    $amount = floatval($_POST['deposit_amount']);
    if ($amount >= 50) {
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, status) VALUES (?, 'deposit', ?, 'pending')");
        $stmt->bind_param("id", $user_id, $amount);
        $stmt->execute();
        $stmt->close();
        $_SESSION['message'] = "Deposit request submitted. Send $amount to TKGnnFaXcvYMstwTnhE4WdbRqDhfA4w3My wallet.";
    } else {
        $_SESSION['message'] = "Minimum deposit is $50.";
    }
    header("Location: account.php");
    exit();
}

// Handle withdraw
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['withdraw'])) {
    $amount = floatval($_POST['withdraw_amount']);
    $wallet = $user['withdraw_wallet'];
    if ($amount > $user['balance']) {
        $_SESSION['message'] = "Insufficient funds.";
    } elseif ($amount <= 0) {
        $_SESSION['message'] = "Invalid amount.";
    } elseif (empty($wallet)) {
        $_SESSION['message'] = "Please set a withdraw address in your profile.";
    } else {
        // Deduct from balance and set withdrawing
        $conn->query("UPDATE users SET balance = balance - $amount, withdrawing = withdrawing + $amount WHERE id = $user_id");
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, wallet_address, status) VALUES (?, 'withdraw', ?, ?, 'pending')");
        $stmt->bind_param("ids", $user_id, $amount, $wallet);
        $stmt->execute();
        $stmt->close();

        $_SESSION['message'] = "Withdrawal request submitted.";
    }
    header("Location: account.php");
    exit();
}

// Get all transactions and referral rewards
$all_transactions = $conn->query("
    SELECT type, amount, created_at FROM transactions WHERE user_id = $user_id AND (status = 'approved' OR type = 'subscription' OR type = 'payout')
    UNION ALL
    SELECT 'referral reward' as type, reward as amount, created_at FROM referrals WHERE referrer_id = $user_id
    ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account</title>
    <link rel="icon" href="p.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><img src="p.png" alt="Profit Pulse" style="height: 40px;"> Profit Pulse</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="welcome.php">Welcome</a></li>
                    <li class="nav-item"><a class="nav-link" href="subscribe.php">Subscribe</a></li>
                    <li class="nav-item"><a class="nav-link" href="referral.php">Referral</a></li>
                    <li class="nav-item"><a class="nav-link active" href="account.php">My Account</a></li>
                    <li class="nav-item"><a class="nav-link" href="chat.php">Chat<?php if ($unread_count > 0): ?><span class="badge bg-danger ms-1"><?php echo $unread_count; ?></span><?php endif; ?></a></li>
                    <li class="nav-item"><a class="btn btn-primary" href="http://localhost/24Earn/index.php">Download APK</a></li>
                </ul>

            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body d-flex flex-row align-items-center">
                        <img src="<?php echo $user['profile_pic'] ?: 'default.png'; ?>" class="img-fluid rounded-circle" alt="Profile Picture" style="width: 100px; height: 100px;">
                        <h5 class="ms-3 fw-bold"><?php echo $user['username']; ?></h5>
                        <p class="ms-3 mb-0 fw-bold">Balance: $<?php echo number_format($user['balance'], 2); ?></p>
                        <p class="ms-3 mb-0 fw-bold">Withdrawing: $<?php echo number_format($user['withdrawing'], 2); ?></p>
                        <button class="btn btn-primary ms-3" data-bs-toggle="modal" data-bs-target="#depositModal">Deposit</button>
                        <button class="btn btn-warning ms-3" data-bs-toggle="modal" data-bs-target="#withdrawModal">Withdraw</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>All Transactions</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-secondary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#transactionsTable">View Transactions</button>
                        <div class="collapse" id="transactionsTable">
                            <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($trans = $all_transactions->fetch_assoc()): ?>
                                    <tr style="color: <?php
                                        if ($trans['type'] == 'subscription') echo 'red';
                                        elseif ($trans['type'] == 'withdraw') echo 'green';
                                        elseif ($trans['type'] == 'referral reward') echo 'blue';
                                        elseif ($trans['type'] == 'deposit') echo 'white';
                                        elseif ($trans['type'] == 'payout') echo 'orange';
                                    ?>">
                                        <td><?php echo ucfirst($trans['type']); ?></td>
                                        <td>$<?php echo number_format($trans['amount'], 2); ?></td>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($trans['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Edit Profile</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-primary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#editForm" aria-expanded="false" aria-controls="editForm">
                            Edit Profile
                        </button>
                        <div class="collapse" id="editForm">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo $user['username']; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password (optional)</label>
                                    <input type="password" class="form-control" id="password" name="password">
                                </div>
                                <div class="mb-3">
                                    <label for="profile_pic" class="form-label">Profile Picture</label>
                                    <input type="file" class="form-control" id="profile_pic" name="profile_pic" accept="image/*">
                                </div>
                                <div class="mb-3">
                                    <label for="withdraw_wallet" class="form-label">Withdraw Wallet Address</label>
                                    <input type="text" class="form-control" id="withdraw_wallet" name="withdraw_wallet" value="<?php echo htmlspecialchars($user['withdraw_wallet'] ?? ''); ?>" placeholder="Enter your withdraw wallet address">
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-success">Update Profile</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12 text-center">
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>

    <!-- Deposit Modal -->
    <div class="modal fade" id="depositModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Deposit Funds</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>(TRC20) Wallet: <strong>TKGnnFaXcvYMstwTnhE4WdbRqDhfA4w3My</strong> <button class="btn btn-sm btn-secondary" onclick="copyToClipboard('TKGnnFaXcvYMstwTnhE4WdbRqDhfA4w3My')">Copy</button></p>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="deposit_amount" class="form-label">Amount (Min $50)</label>
                            <input type="number" class="form-control" id="deposit_amount" name="deposit_amount" min="50" step="0.01" required>
                        </div>
                        <button type="submit" name="deposit" class="btn btn-primary">Submit Deposit</button>
                    </form>
                </div>

                <div class="modal-body"> 
                <h6>NOTE</h6>
                <p> While depositing add $1 for transacton fees <br> ie: if you are depositing $50 you initiate a ($51) transaction  <br/></p>
                <p> If deposits dont reflect please contact the admin with a screenshot of your transaction </p>
                 </div>

            </div>
        </div>
    </div>

    <!-- Withdraw Modal -->
    <div class="modal fade" id="withdrawModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Withdraw Funds</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="withdraw_amount" class="form-label">Amount</label>
                            <input type="number" class="form-control" id="withdraw_amount" name="withdraw_amount" max="<?php echo $user['balance']; ?>" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Withdraw Address</label>
                            <p><?php echo htmlspecialchars($user['withdraw_wallet'] ?? 'No address set'); ?></p>
                            <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#editForm">Add/Edit Address</button>
                        </div>
                        <button type="submit" name="withdraw" class="btn btn-warning">Submit Withdrawal</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>

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

// Get user balance
$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$balance = $user['balance'];
$stmt->close();

// Handle subscription
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bouque'])) {
    $bouque = $_POST['bouque'];
    $fees = ['basic' => 50, 'premium' => 100, 'vip' => 200];
    $periods = ['basic' => 30, 'premium' => 60, 'vip' => 90];

    if (array_key_exists($bouque, $fees)) {
        $fee = $fees[$bouque];
        if ($balance >= $fee) {
            // Deduct fee
            $conn->query("UPDATE users SET balance = balance - $fee WHERE id = $user_id");

            // Insert subscription
            $stmt = $conn->prepare("INSERT INTO subscriptions (user_id, bouque_name, fee, period_days) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isdi", $user_id, $bouque, $fee, $periods[$bouque]);
            $stmt->execute();
            $stmt->close();

            // Insert transaction
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount) VALUES (?, 'subscription', ?)");
            $stmt->bind_param("id", $user_id, $fee);
            $stmt->execute();
            $stmt->close();

            $_SESSION['message'] = "Subscription successful!";
        } else {
            $_SESSION['message'] = "Insufficient balance!";
        }
    }
    header("Location: subscribe.php");
    exit();
}

// Handle payout
$payout_message = '';
if (isset($_SESSION['payout_message'])) {
    $payout_message = $_SESSION['payout_message'];
    unset($_SESSION['payout_message']);
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['payout'])) {
    $subscription_id = $_POST['subscription_id'];

    // Check if subscription exists and belongs to user
    $stmt = $conn->prepare("SELECT * FROM subscriptions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $subscription_id, $user_id);
    $stmt->execute();
    $sub = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($sub) {
        // Check if 10 seconds have passed since last payout for this subscription
        $last_payout = $conn->query("SELECT created_at FROM transactions WHERE user_id = $user_id AND subscription_id = $subscription_id AND type = 'payout' ORDER BY created_at DESC LIMIT 1");
        $last_payout_time = $last_payout->num_rows > 0 ? strtotime($last_payout->fetch_assoc()['created_at']) : 0;

        if ((time() - $last_payout_time) >= 10) { // Every 10 seconds
            $payout = $sub['fee'] * 0.039; // 3.9% of subscription fee
            $update_stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $update_stmt->bind_param("di", $payout, $user_id);
            if ($update_stmt->execute()) {
                $stmt = $conn->prepare("INSERT INTO transactions (user_id, subscription_id, type, amount) VALUES (?, ?, 'payout', ?)");
                $stmt->bind_param("iid", $user_id, $subscription_id, $payout);
                $stmt->execute();
                $stmt->close();

                $_SESSION['payout_message'] = "Payout successful!";
            } else {
                $_SESSION['payout_message'] = "Error processing payout.";
            }
            $update_stmt->close();
        } else {
            $_SESSION['payout_message'] = "Please wait 10 seconds before claiming another payout.";
        }
    }
    header("Location: subscribe.php");
    exit();
}

// Get subscribed bouques
$subscriptions = $conn->query("SELECT * FROM subscriptions WHERE user_id = $user_id ORDER BY subscribed_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribe</title>
    <link rel="icon" href="p.png">
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
                    <li class="nav-item"><a class="nav-link active" href="subscribe.php">Subscribe</a></li>
                    <li class="nav-item"><a class="nav-link" href="referral.php">Referral</a></li>
                    <li class="nav-item"><a class="nav-link" href="account.php">My Account</a></li>
                    <li class="nav-item"><a class="nav-link" href="chat.php">Chat<?php if ($unread_count > 0): ?><span class="badge bg-danger ms-1"><?php echo $unread_count; ?></span><?php endif; ?></a></li>
                </ul>

            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2>Subscription Options</h2>
        <p>Your current balance: $<?php echo number_format($balance, 2); ?></p>
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Basic Bouque</h5>
                        <p class="card-text">Fee: $50<br>Period: 30 days<br>Payout: 3.9% every 6 days/week</p>
                        <form method="POST">
                            <input type="hidden" name="bouque" value="basic">
                            <button type="submit" class="btn btn-primary" <?php echo $balance < 50 ? 'disabled' : ''; ?>>Subscribe</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Premium Bouque</h5>
                        <p class="card-text">Fee: $100<br>Period: 60 days<br>Payout: 3.9% every 6 days/week</p>
                        <form method="POST">
                            <input type="hidden" name="bouque" value="premium">
                            <button type="submit" class="btn btn-primary" <?php echo $balance < 100 ? 'disabled' : ''; ?>>Subscribe</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">VIP Bouque</h5>
                        <p class="card-text">Fee: $200<br>Period: 90 days<br>Payout: 3.9% every 6 days/week</p>
                        <form method="POST">
                            <input type="hidden" name="bouque" value="vip">
                            <button type="submit" class="btn btn-primary" <?php echo $balance < 200 ? 'disabled' : ''; ?>>Subscribe</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="mt-5">Your Subscriptions</h3>
        <?php if ($payout_message): ?>
            <div id="payoutAlert" class="alert alert-success"><?php echo $payout_message; ?></div>
        <?php endif; ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Bouque</th>
                    <th>Fee</th>
                    <th>Period</th>
                    <th>Subscribed At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($sub = $subscriptions->fetch_assoc()): ?>
                    <?php
                    // Get last payout time for this subscription
                    $last_payout_query = $conn->query("SELECT created_at FROM transactions WHERE user_id = $user_id AND subscription_id = {$sub['id']} AND type = 'payout' ORDER BY created_at DESC LIMIT 1");
                    $last_payout_time = $last_payout_query->num_rows > 0 ? strtotime($last_payout_query->fetch_assoc()['created_at']) : 0;
                    $can_payout_sub = (time() - $last_payout_time) >= 10;
                    $last_payout_timestamp = $last_payout_time * 1000;
                    ?>
                    <tr>
                        <td><?php echo ucfirst($sub['bouque_name']); ?></td>
                        <td>$<?php echo number_format($sub['fee'], 2); ?></td>
                        <td><?php echo $sub['period_days']; ?> days</td>
                        <td><?php echo $sub['subscribed_at']; ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="subscription_id" value="<?php echo $sub['id']; ?>">
                                <button type="submit" name="payout" class="btn <?php echo $can_payout_sub ? 'btn-success' : 'btn-danger'; ?> btn-sm" data-last-payout="<?php echo $last_payout_timestamp; ?>" <?php echo !$can_payout_sub ? 'disabled' : ''; ?>>Payout</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    <script>
        // Auto-hide payout alert after 2 seconds
        setTimeout(function() {
            var alert = document.getElementById('payoutAlert');
            if (alert) {
                alert.style.display = 'none';
            }
        }, 2000);

        // Auto-enable payout buttons independently after 10 seconds
        var cooldownTime = 10000; // 10 seconds in milliseconds

        function updatePayoutButtons() {
            var now = Date.now();
            var payoutButtons = document.querySelectorAll('button[name="payout"]');

            payoutButtons.forEach(function(button) {
                var lastPayoutTime = parseInt(button.getAttribute('data-last-payout')) || 0;
                var timeSinceLastPayout = now - lastPayoutTime;

                if (timeSinceLastPayout >= cooldownTime) {
                    button.classList.remove('btn-danger');
                    button.classList.add('btn-success');
                    button.disabled = false;
                } else {
                    button.classList.remove('btn-success');
                    button.classList.add('btn-danger');
                    button.disabled = true;
                }
            });
        }

        // Start checking every second if buttons need to be enabled
        updatePayoutButtons(); // Initial check
        setInterval(updatePayoutButtons, 1000);
    </script>
</body>
</html>

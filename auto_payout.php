include 'config.php';

// Function to process automatic payouts every 10 seconds
function processAutoPayouts() {
    global $conn;
    // Get all users with active subscriptions
    $users = $conn->query("SELECT u.id, u.balance, s.fee, s.subscribed_at, s.period_days
                          FROM users u
                          JOIN subscriptions s ON u.id = s.user_id
                          WHERE s.subscribed_at >= DATE_SUB(NOW(), INTERVAL s.period_days DAY)");

    while ($user = $users->fetch_assoc()) {
        $user_id = $user['id'];
        $subscription_fee = $user['fee'];
        $subscribed_at = strtotime($user['subscribed_at']);
        $seconds_since_sub = time() - $subscribed_at;

        // Check if 10 seconds have passed since last payout or subscription
        $last_payout = $conn->query("SELECT created_at FROM transactions WHERE user_id = $user_id AND type = 'payout' ORDER BY created_at DESC LIMIT 1");
        $last_payout_time = $last_payout->num_rows > 0 ? strtotime($last_payout->fetch_assoc()['created_at']) : $subscribed_at;

        if ((time() - $last_payout_time) >= 10) { // Every 10 seconds
            $payout = $subscription_fee * 0.039; // 3.9% of subscription fee
            $conn->query("UPDATE users SET balance = balance + $payout WHERE id = $user_id");
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount) VALUES (?, 'payout', ?)");
            $stmt->bind_param("id", $user_id, $payout);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Run the payout process
processAutoPayouts();

echo "Automatic subscription payouts processed successfully.";
?>

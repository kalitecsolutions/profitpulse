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

// Get user referral code
$stmt = $conn->prepare("SELECT referral_code FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$referral_code = $user['referral_code'];
$stmt->close();

// Get referred users
$referred_users = $conn->query("SELECT u.username, r.created_at FROM referrals r JOIN users u ON r.referred_id = u.id WHERE r.referrer_id = $user_id ORDER BY r.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invite</title>
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
                    <li class="nav-item"><a class="nav-link active" href="referral.php">Referral</a></li>
                    <li class="nav-item"><a class="nav-link" href="account.php">My Account</a></li>
                    <li class="nav-item"><a class="nav-link" href="chat.php">Chat<?php if ($unread_count > 0): ?><span class="badge bg-danger ms-1"><?php echo $unread_count; ?></span><?php endif; ?></a></li>
                </ul>

            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2>Referral Program</h2>
        <p>When a user invites another user, they get $1 for each user that registers using their referral code. If the invited user also invites another successful user (who registers and subscribes), the original referrer gets an additional $0.5.</p>
        
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Your Referral Code</h5>
                <p>Share this link to invite friends: <strong><?php echo "http://localhost/24Earn/index.php?ref=" . $referral_code; ?></strong></p>
                <button class="btn btn-secondary" onclick="copyToClipboard('<?php echo "http://localhost/24Earn/index.php?ref=" . $referral_code; ?>')">Copy Link</button>
            </div>
        </div>

        <h3>Users You Referred</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Referred At</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($ref = $referred_users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $ref['username']; ?></td>
                        <td><?php echo $ref['created_at']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>

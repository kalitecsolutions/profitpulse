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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit Pulse</title>
    <link rel="icon" href="p.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .hero {
            background-image: url('rr.jpg');
            background-size: cover;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        .movie-card {
            background: linear-gradient(145deg, #1c1c1c, #4b134f, #ff512f);
            border: none;
            color: white;
            transition: transform 0.3s;
        }
        .movie-card:hover {
            transform: scale(1.05);
        }
        .movie-poster {
            height: 150px;
            object-fit: cover;
            border-radius: 0.375rem 0.375rem 0 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#"><img src="p.png" alt="Profit Pulse" style="height: 40px;"> Profit Pulse</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="welcome.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="subscribe.php">Subscribe</a></li>
                    <li class="nav-item"><a class="nav-link" href="referral.php">Referrals</a></li>
                    <li class="nav-item"><a class="nav-link" href="account.php">My Account</a></li>
                    <li class="nav-item"><a class="nav-link" href="chat.php">Chat<?php if ($unread_count > 0): ?><span class="badge bg-danger ms-1"><?php echo $unread_count; ?></span><?php endif; ?></a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="hero welcome-hero">
        <div class="container">
            <img src="p.png" alt="Profit Pulse Logo" class="display-4" style="height: 300px; margin-bottom: 20px;">
        </div>
    </div>

    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h2 class="text-white mb-4">How It Works</h2>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card movie-card mb-3">
                            <div class="card-body">
                                <h5><i class="fas fa-user-plus"></i> Step 1: Join the Club</h5>
                                <p>Register and get your unique referral code to invite friends.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card movie-card mb-3">
                            <div class="card-body">
                                <h5><i class="fas fa-credit-card"></i> Step 2: Fund Your Account</h5>
                                <p>Deposit funds to unlock premium movie packages.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card movie-card mb-3">
                            <div class="card-body">
                                <h5><i class="fas fa-chart-line"></i> Step 3: Select Your Profit Plan</h5>
                                <p>Subscribe to a profit plan and start earning.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card movie-card mb-3">
                            <div class="card-body">
                                <h5><i class="fas fa-gift"></i> Step 4: Earn & Enjoy</h5>
                                <p>Receive payouts and invite friends for bonuses.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-12">
                <h2 class="text-white mb-4">Profit Plans</h2>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card movie-card mb-3">
                            <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" class="card-img-top movie-poster" alt="Basic Package">
                            <div class="card-body">
                                <h5 class="card-title">Basic Profit Plan</h5>
                                <p class="card-text">Subscription Fee: $50<br>Access Period: 30 days<br>3.9% daily payouts</p>
                                <a href="subscribe.php" class="btn btn-primary">Subscribe Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card movie-card mb-3">
                            <img src="https://images.unsplash.com/photo-1440404653325-ab127d49abc1?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" class="card-img-top movie-poster" alt="Premium Package">
                            <div class="card-body">
                                <h5 class="card-title">Premium Profit Plan</h5>
                                <p class="card-text">Subscription Fee: $100<br>Access Period: 60 days<br>3.9% daily payouts</p>
                                <a href="subscribe.php" class="btn btn-primary">Subscribe Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card movie-card mb-3">
                            <img src="https://images.unsplash.com/photo-1559526324-4b87b5e36e44?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" class="card-img-top movie-poster" alt="VIP Package">
                            <div class="card-body">
                                <h5 class="card-title">VIP Profit Plan</h5>
                                <p class="card-text">Subscription Fee: $200<br>Access Period: 90 days<br>3.9% daily payouts</p>
                                <a href="subscribe.php" class="btn btn-primary">Subscribe Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <script src="script.js"></script>
</body>
</html>

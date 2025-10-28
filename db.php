<?php
include 'config.php';

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db(DB_NAME);

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    referral_code VARCHAR(10) UNIQUE,
    balance DECIMAL(10,2) DEFAULT 0.00,
    withdrawing DECIMAL(10,2) DEFAULT 0.00,
    profile_pic VARCHAR(255) DEFAULT 'default.png',
    role ENUM('admin', 'subscriber') DEFAULT 'subscriber',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create subscriptions table
$sql = "CREATE TABLE IF NOT EXISTS subscriptions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    bouque_name VARCHAR(100) NOT NULL,
    fee DECIMAL(10,2) NOT NULL,
    payout_rate DECIMAL(5,2) DEFAULT 3.90,
    period_days INT(11) DEFAULT 30,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Subscriptions table created successfully<br>";
} else {
    echo "Error creating subscriptions table: " . $conn->error . "<br>";
}

// Create referrals table
$sql = "CREATE TABLE IF NOT EXISTS referrals (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT(11) NOT NULL,
    referred_id INT(11) NOT NULL,
    level INT(11) DEFAULT 1,
    reward DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Referrals table created successfully<br>";
} else {
    echo "Error creating referrals table: " . $conn->error . "<br>";
}

// Create messages table
$sql = "CREATE TABLE IF NOT EXISTS messages (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Messages table created successfully<br>";
} else {
    echo "Error creating messages table: " . $conn->error . "<br>";
}

// Create transactions table
$sql = "CREATE TABLE IF NOT EXISTS transactions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    type ENUM('deposit', 'withdraw', 'payout', 'subscription') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    wallet_address VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Transactions table created successfully<br>";
} else {
    echo "Error creating transactions table: " . $conn->error . "<br>";
}

// Insert default admin user if not exists (password: admin123)
$admin_password = '$2y$10$e5Buu0CGt.K9HdhQ6NDWhenclWahtls.P6Kcts0INuXnDoqPSeuCy';
$admin_referral_code = 'ADMIN123';
$sql = "INSERT IGNORE INTO users (username, phone, password, referral_code, role) VALUES ('admin', '+1234567890', '$admin_password', '$admin_referral_code', 'admin')";
if ($conn->query($sql) === TRUE) {
    echo "Default admin user created<br>";
} else {
    echo "Error creating admin user: " . $conn->error . "<br>";
}

echo "Database setup complete! Alternatively, you can import the db.sql file into phpMyAdmin.";
?>

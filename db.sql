-- 24Earn Database Setup SQL
-- Run this in phpMyAdmin or MySQL command line

CREATE DATABASE IF NOT EXISTS 24earn;
USE 24earn;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    referral_code VARCHAR(10) UNIQUE,
    balance DECIMAL(10,2) DEFAULT 0.00,
    withdrawing DECIMAL(10,2) DEFAULT 0.00,
    profile_pic VARCHAR(255) DEFAULT 'default.png',
    withdraw_wallet VARCHAR(255),
    role ENUM('admin', 'subscriber') DEFAULT 'subscriber',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create subscriptions table
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    bouque_name VARCHAR(100) NOT NULL,
    fee DECIMAL(10,2) NOT NULL,
    payout_rate DECIMAL(5,2) DEFAULT 3.90,
    period_days INT(11) DEFAULT 30,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create referrals table
CREATE TABLE IF NOT EXISTS referrals (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT(11) NOT NULL,
    referred_id INT(11) NOT NULL,
    level INT(11) DEFAULT 1,
    reward DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    message TEXT,
    image VARCHAR(255),
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    subscription_id INT(11) DEFAULT NULL,
    type ENUM('deposit', 'withdraw', 'payout', 'subscription') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    wallet_address VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
);

-- Create user_withdraw_addresses table
CREATE TABLE IF NOT EXISTS user_withdraw_addresses (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    address VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO users (username, phone, password, referral_code, role) VALUES ('admin', '+1234567890', '$2y$10$e5Buu0CGt.K9HdhQ6NDWhenclWahtls.P6Kcts0INuXnDoqPSeuCy', 'ADMIN123', 'admin');

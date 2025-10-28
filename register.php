<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $referral_code = trim($_POST['referral_code']);

    $errors = [];

    // Validate inputs
    if (empty($username) || empty($phone) || empty($password) || empty($confirm_password) || empty($referral_code)) {
        $errors[] = "All fields are required.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    // Check if referral code exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE referral_code = ?");
    $stmt->bind_param("s", $referral_code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $errors[] = "Invalid referral code.";
    } else {
        $referrer = $result->fetch_assoc();
        $referrer_id = $referrer['id'];
    }
    $stmt->close();

    // Check for indirect referral (if referrer has a referrer)
    $indirect_referrer_id = null;
    if ($referrer_id) {
        $stmt = $conn->prepare("SELECT referrer_id FROM referrals WHERE referred_id = ?");
        $stmt->bind_param("i", $referrer_id);
        $stmt->execute();
        $indirect_result = $stmt->get_result();
        if ($indirect_result->num_rows > 0) {
            $indirect = $indirect_result->fetch_assoc();
            $indirect_referrer_id = $indirect['referrer_id'];
        }
        $stmt->close();
    }

    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Username already exists.";
    }
    $stmt->close();

    // Check if phone already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Contact already exists.";
    }
    $stmt->close();

    if (empty($errors)) {
        // Generate unique referral code
        $new_referral_code = substr(md5(uniqid(mt_rand(), true)), 0, 10);

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (username, phone, password, referral_code) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $phone, $hashed_password, $new_referral_code);
        if ($stmt->execute()) {
            $new_user_id = $conn->insert_id;

            // Add to referrals table
            $stmt2 = $conn->prepare("INSERT INTO referrals (referrer_id, referred_id, reward) VALUES (?, ?, 1.00)");
            $stmt2->bind_param("ii", $referrer_id, $new_user_id);
            $stmt2->execute();
            $stmt2->close();

            // Update referrer's balance
            $conn->query("UPDATE users SET balance = balance + 1.00 WHERE id = $referrer_id");

            // Check if indirect referrer exists and reward $0.5
            if ($indirect_referrer_id) {
                $conn->query("UPDATE users SET balance = balance + 0.50 WHERE id = $indirect_referrer_id");
            }

            $_SESSION['success'] = "Registration successful! You can now login.";
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
        $stmt->close();
    }

    $_SESSION['errors'] = $errors;
    header("Location: index.php");
    exit();
}
?>

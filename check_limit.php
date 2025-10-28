<?php
session_start();
include 'config.php';

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE user_id = ? AND DATE(sent_at) = ?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

header('Content-Type: application/json');
echo json_encode(['limit_reached' => $result['count'] >= 24]);
?>

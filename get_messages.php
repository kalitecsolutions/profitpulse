<?php
include 'config.php';

// Delete old messages and their associated images
$old_messages = $conn->query("SELECT image FROM messages WHERE sent_at < DATE_SUB(NOW(), INTERVAL 1 DAY) AND image IS NOT NULL");
while ($msg = $old_messages->fetch_assoc()) {
    if (file_exists($msg['image'])) {
        unlink($msg['image']);
    }
}
$conn->query("DELETE FROM messages WHERE sent_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");

// Get messages
$messages = $conn->query("SELECT m.id, m.message, m.image, m.sent_at, m.user_id, u.username, u.profile_pic FROM messages m JOIN users u ON m.user_id = u.id ORDER BY m.sent_at DESC LIMIT 50");

$result = [];
while ($msg = $messages->fetch_assoc()) {
    $result[] = $msg;
}

header('Content-Type: application/json');
echo json_encode($result);
?>

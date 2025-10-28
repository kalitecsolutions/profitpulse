<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'subscriber') {
    header("Location: index.php");
    exit();
}
include 'config.php';
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['message']) || isset($_FILES['image']))) {
    $message = trim($_POST['message'] ?? '');
    $image_path = null;

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 300 * 1024; // 300KB

        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $unique_name = uniqid() . '.' . $ext;
            $upload_path = 'uploads/' . $unique_name;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $image_path = $upload_path;
                if (empty($message)) {
                    $message = 'Image';
                }
            }
        }
    }

    if (!empty($message) || $image_path) {
        // Check daily limit (24 messages)
        $today = date('Y-m-d');
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE user_id = ? AND DATE(sent_at) = ?");
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result['count'] < 24) {
            $stmt = $conn->prepare("INSERT INTO messages (user_id, message, image) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $message, $image_path);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: chat.php");
    exit();
}

// Delete messages older than 24 hours and their associated images
$old_messages = $conn->query("SELECT image FROM messages WHERE sent_at < DATE_SUB(NOW(), INTERVAL 1 DAY) AND image IS NOT NULL");
while ($msg = $old_messages->fetch_assoc()) {
    if (file_exists($msg['image'])) {
        unlink($msg['image']);
    }
}
$conn->query("DELETE FROM messages WHERE sent_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");

// Get messages
$messages = $conn->query("SELECT m.id, m.message, m.sent_at, m.user_id, u.username, u.profile_pic FROM messages m JOIN users u ON m.user_id = u.id ORDER BY m.sent_at ASC LIMIT 50");

// Get last message ID for unread tracking
$last_message_id = 0;
$messages_array = [];
while ($msg = $messages->fetch_assoc()) {
    $messages_array[] = $msg;
    $last_message_id = $msg['id'];
}

// Update last read message ID in session
if ($last_message_id > 0) {
    $_SESSION['last_read_message_id'] = $last_message_id;
}

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
    <title>Chat-room</title>
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
                    <li class="nav-item"><a class="nav-link" href="account.php">My Account</a></li>
                    <li class="nav-item"><a class="nav-link active" href="chat.php">Chat</a></li>
                </ul>

            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2>Chat Room</h2>
        <p>You can send up to 24 messages per day. Messages are deleted after 24 hours.</p>

        <div id="chat-box" class="border p-3 mb-3" style="height: 400px; overflow-y: scroll;">
            <?php foreach ($messages_array as $msg): ?>
                <div class="message mb-2 <?php echo ($msg['user_id'] == $user_id) ? 'sent' : 'received'; ?>" data-message-id="<?php echo $msg['id']; ?>">
                    <img src="<?php echo $msg['profile_pic'] ?: 'default.png'; ?>" class="rounded-circle me-2" style="width: 30px; height: 30px;" alt="Profile">
                    <strong><?php echo $msg['username']; ?>:</strong>
                    <?php if (!empty($msg['image'])): ?>
                        <img src="<?php echo $msg['image']; ?>" class="img-fluid" style="max-width: 600px; max-height: 600px;" alt="Shared Image">
                        <?php if ($msg['message'] != 'Image'): ?>
                            <br><?php echo htmlspecialchars($msg['message']); ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php echo htmlspecialchars($msg['message']); ?>
                    <?php endif; ?>
                    <small class="text-muted"><?php echo date('H:i', strtotime($msg['sent_at'])); ?></small>
                    <button class="btn btn-sm btn-outline-secondary ms-2 reply-btn" data-username="<?php echo $msg['username']; ?>" data-message="<?php echo htmlspecialchars($msg['message']); ?>" title="Reply to this message">
                        <i class="fas fa-reply"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="reply-indicator" class="alert alert-info d-none">
            <small>Replying to <strong id="reply-username"></strong>: "<span id="reply-message"></span>"</small>
            <button type="button" class="btn-close btn-sm" id="cancel-reply"></button>
        </div>

        <form method="POST" enctype="multipart/form-data" id="message-form">
            <div class="input-group mb-2">
                <input type="text" class="form-control" name="message" id="message" placeholder="Type your message..." maxlength="500">
                <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/gif" style="display: none;">
                <button type="button" class="btn btn-outline-secondary" id="attach-btn" title="Attach Image">📎</button>
                <button type="submit" class="btn btn-primary" id="send-btn">Send</button>
            </div>
            <small class="text-muted">Max image size: 300KB. Supported formats: JPG, PNG, GIF.</small>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    <script>
        // Reply functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('reply-btn') || e.target.closest('.reply-btn')) {
                const btn = e.target.classList.contains('reply-btn') ? e.target : e.target.closest('.reply-btn');
                const username = btn.getAttribute('data-username');
                const message = btn.getAttribute('data-message');

                document.getElementById('reply-username').textContent = username;
                document.getElementById('reply-message').textContent = message;
                document.getElementById('reply-indicator').classList.remove('d-none');
                document.getElementById('message').focus();
            }
        });

        document.getElementById('cancel-reply').addEventListener('click', function() {
            document.getElementById('reply-indicator').classList.add('d-none');
        });

        // Simple polling for new messages
        setInterval(function() {
            fetch('get_messages.php')
                .then(response => response.json())
                .then(data => {
                    const chatBox = document.getElementById('chat-box');
                    chatBox.innerHTML = '';
                    data.forEach(msg => {
                        const msgDiv = document.createElement('div');
                        msgDiv.className = 'message mb-2';
                        msgDiv.setAttribute('data-message-id', msg.id);
                        const isSent = msg.user_id == <?php echo $user_id; ?>;
                        msgDiv.className = `message mb-2 ${isSent ? 'sent' : 'received'}`;
                        let content = `<img src="${msg.profile_pic || 'default.png'}" class="rounded-circle me-2" style="width: 30px; height: 30px;" alt="Profile">
                            <strong>${msg.username}:</strong> `;
                        if (msg.image) {
                            content += `<img src="${msg.image}" class="img-fluid" style="max-width: 600px; max-height: 600px;" alt="Shared Image">`;
                            if (msg.message !== 'Image') {
                                content += `<br>${msg.message}`;
                            }
                        } else {
                            content += msg.message;
                        }
                        content += `<small class="text-muted">${new Date(msg.sent_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</small>
                            <button class="btn btn-sm btn-outline-secondary ms-2 reply-btn" data-username="${msg.username}" data-message="${msg.message}" title="Reply to this message">
                                <i class="fas fa-reply"></i>
                            </button>`;
                        msgDiv.innerHTML = content;
                        chatBox.appendChild(msgDiv);
                    });
                    chatBox.scrollTop = chatBox.scrollHeight;
                });
        }, 5000); // Poll every 5 seconds

        // Attach image button functionality
        document.getElementById('attach-btn').addEventListener('click', function() {
            document.getElementById('image').click();
        });

        // Check message limit
        document.getElementById('message-form').addEventListener('submit', function(e) {
            fetch('check_limit.php')
                .then(response => response.json())
                .then(data => {
                    if (data.limit_reached) {
                        e.preventDefault();
                        alert('You have reached your daily message limit of 24.');
                        document.getElementById('send-btn').disabled = true;
                    }
                });
        });
    </script>
</body>
</html>

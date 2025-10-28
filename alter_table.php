<?php
include 'config.php';
$result = $conn->query('ALTER TABLE messages ADD COLUMN image VARCHAR(255)');
if ($result) {
    echo 'Column added successfully';
} else {
    echo 'Error: ' . $conn->error;
}
?>

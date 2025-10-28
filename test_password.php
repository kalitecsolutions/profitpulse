<?php
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$password = 'admin123';

if (password_verify($password, $hash)) {
    echo "Password matches!";
} else {
    echo "Password does not match!";
}
?>

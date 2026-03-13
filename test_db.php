<?php
require_once 'config/database.php';
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute(['admin']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    echo "User admin found.\n";
    echo "Hashed password: " . $user['password'] . "\n";
    if (password_verify('admin123', $user['password'])) {
        echo "Password verification success!\n";
    } else {
        echo "Password verification failed.\n";
    }
} else {
    echo "User admin NOT found.\n";
}
unlink(__FILE__); // Small cleanup

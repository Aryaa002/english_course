<?php
require_once 'config/database.php';

// Password baru
$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update password admin
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
if ($stmt->execute([$hashed_password])) {
    echo "<h3 style='color: green;'>✅ Password admin berhasil direset!</h3>";
    echo "<p>Username: <strong>admin</strong></p>";
    echo "<p>Password: <strong>admin123</strong></p>";
    echo "<a href='login.php'>Klik untuk login</a>";
} else {
    echo "<h3 style='color: red;'>❌ Gagal reset password. Pastikan user admin ada di database.</h3>";
}

// Tampilkan semua user
echo "<h4>Daftar User:</h4>";
$stmt = $pdo->query("SELECT id, username, email, role FROM users");
$users = $stmt->fetchAll();
echo "<ul>";
foreach ($users as $user) {
    echo "<li>ID: {$user['id']} - Username: {$user['username']} - Email: {$user['email']} - Role: {$user['role']}</li>";
}
echo "</ul>";
?>
<?php
$host = 'localhost';
$dbname = 'english_course';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

session_start();

// Base URL
if (!defined('BASE_URL')) {
    define('BASE_URL', '/english-course/');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

function redirect($url) {
    // Jika URL sudah absolute (http:// atau https://)
    if (preg_match('#^https?://#', $url)) {
        header("Location: $url");
    } 
    // Jika URL sudah dimulai dengan BASE_URL
    elseif (strpos($url, BASE_URL) === 0) {
        header("Location: $url");
    }
    // Jika URL dimulai dengan / (root)
    elseif (strpos($url, '/') === 0) {
        header("Location: $url");
    }
    // Tambahkan BASE_URL
    else {
        header("Location: " . BASE_URL . ltrim($url, '/'));
    }
    exit();
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        redirect('login.php');
    }
}
?>
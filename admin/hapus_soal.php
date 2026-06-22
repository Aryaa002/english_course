<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM soal_latihan WHERE id = ?");
    $stmt->execute([$id]);
}

redirect('soal.php');
?>
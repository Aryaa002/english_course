<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM kuis WHERE id = ?");
    $stmt->execute([$id]);
}

redirect('kuis.php');
?>
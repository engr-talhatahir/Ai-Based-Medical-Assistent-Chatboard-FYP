<?php
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id > 0 && $user_id != $_SESSION['user_id']) {
    $stmt = $pdo->prepare("DELETE FROM chat_messages WHERE user_id = ?");
    $stmt->execute(array($user_id));
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute(array($user_id));
}

header('Location: admin_dashboard.php');
exit();
?>
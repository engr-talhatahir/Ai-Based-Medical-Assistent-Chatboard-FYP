<?php
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$role = isset($_POST['role']) ? $_POST['role'] : '';

if ($user_id > 0 && $user_id != $_SESSION['user_id'] && ($role == 'user' || $role == 'admin')) {
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute(array($role, $user_id));
}

header('Location: admin_dashboard.php');
exit();
?>
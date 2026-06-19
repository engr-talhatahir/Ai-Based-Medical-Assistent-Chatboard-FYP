<?php
// clear_all_messages.php
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("DELETE FROM chat_messages WHERE user_id = ?");
$success = $stmt->execute(array($userId));

echo json_encode(['success' => $success]);
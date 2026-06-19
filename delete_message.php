<?php
// delete_message.php
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized or missing ID']);
    exit();
}

$messageId = intval($_GET['id']);
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("DELETE FROM chat_messages WHERE id = ? AND user_id = ?");
$success = $stmt->execute(array($messageId, $userId));

echo json_encode(['success' => $success]);
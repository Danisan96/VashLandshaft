<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

header('Content-Type: application/json');

$current_user_id = $_SESSION['user']['id'];
$contact_user_id = (int)$_GET['user_id'];

try {
    // Получаем сообщения
    $stmt = $pdo->prepare("
        SELECT 
            m.*,
            DATE_FORMAT(m.created_at, '%H:%i') as time
        FROM messages m
        WHERE 
            (m.sender_id = ? AND m.recipient_id = ?) OR
            (m.sender_id = ? AND m.recipient_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$current_user_id, $contact_user_id, $contact_user_id, $current_user_id]);
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Помечаем сообщения как прочитанные
    $pdo->prepare("
        UPDATE messages 
        SET is_read = TRUE 
        WHERE sender_id = ? AND recipient_id = ? AND is_read = FALSE
    ")->execute([$contact_user_id, $current_user_id]);
    
    echo json_encode($messages);
    
} catch (PDOException $e) {
    Logger::logError("Get messages error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>
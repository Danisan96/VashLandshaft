<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

try {
    // Получаем список чатов
    $chats = $pdo->prepare("
        SELECT 
            u.id as user_id,
            u.username,
            u.avatar_path,
            m.message as last_message,
            m.created_at as last_message_time,
            m.is_read,
            COUNT(CASE WHEN m.is_read = 0 AND m.recipient_id = ? THEN 1 END) as unread_count
        FROM (
            SELECT 
                CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END as contact_id,
                MAX(created_at) as max_time
            FROM messages
            WHERE sender_id = ? OR recipient_id = ?
            GROUP BY contact_id
        ) as last_messages
        JOIN messages m ON 
            (m.sender_id = ? AND m.recipient_id = last_messages.contact_id OR 
             m.sender_id = last_messages.contact_id AND m.recipient_id = ?) AND
            m.created_at = last_messages.max_time
        JOIN users u ON u.id = last_messages.contact_id
        GROUP BY u.id, u.username, u.avatar_path, m.message, m.created_at, m.is_read
        ORDER BY m.created_at DESC
    ");
    $chats->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
    
    // Получаем сообщения для выбранного чата
    $selected_chat = null;
    $messages = [];
    if (isset($_GET['chat_with'])) {
        $contact_id = (int)$_GET['chat_with'];
        
        // Помечаем сообщения как прочитанные
        $pdo->prepare("
            UPDATE messages 
            SET is_read = 1 
            WHERE sender_id = ? AND recipient_id = ? AND is_read = 0
        ")->execute([$contact_id, $user_id]);
        
        // Получаем сообщения
        $msgStmt = $pdo->prepare("
            SELECT 
                m.*,
                DATE_FORMAT(m.created_at, '%H:%i') as time,
                u.username as sender_name,
                u.avatar_path as sender_avatar
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE 
                (m.sender_id = ? AND m.recipient_id = ?) OR
                (m.sender_id = ? AND m.recipient_id = ?)
            ORDER BY m.created_at ASC
        ");
        $msgStmt->execute([$user_id, $contact_id, $contact_id, $user_id]);
        $messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Получаем информацию о собеседнике
        $userStmt = $pdo->prepare("SELECT id, username, avatar_path FROM users WHERE id = ?");
        $userStmt->execute([$contact_id]);
        $selected_chat = $userStmt->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    Logger::logError("Messages error: " . $e->getMessage());
    $error = "Ошибка загрузки сообщений";
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои сообщения</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #111;
            color: #fff;
        }
        header {
            background-color: #000;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #cc0000;
        }
        .btn-home {
            padding: 8px 16px;
            background-color: #cc0000;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            border: none;
        }
        .messages-container {
            max-width: 1200px;
            margin: 30px auto;
            display: flex;
            height: calc(100vh - 200px);
        }
        .chat-list {
            width: 300px;
            background: #222;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            margin-right: 20px;
            overflow-y: auto;
            border: 1px solid #333;
        }
        .chat-item {
            padding: 15px;
            border-bottom: 1px solid #333;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .chat-item:hover {
            background-color: #333;
        }
        .chat-item.active {
            background-color: #330000;
        }
        .chat-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        .chat-info {
            flex-grow: 1;
        }
        .chat-name {
            font-weight: bold;
            margin-bottom: 5px;
            color: #fff;
        }
        .chat-last-message {
            font-size: 14px;
            color: #ccc;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .chat-time {
            font-size: 12px;
            color: #999;
        }
        .chat-unread {
            background-color: #cc0000;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .chat-window {
            flex-grow: 1;
            background: #222;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
            border: 1px solid #333;
        }
        .chat-header {
            padding: 15px;
            border-bottom: 1px solid #333;
            font-weight: bold;
            display: flex;
            align-items: center;
            color: #fff;
        }
        .chat-avatar-large {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
        }
        .chat-messages {
            flex-grow: 1;
            padding: 15px;
            overflow-y: auto;
            background-color: #1a1a1a;
        }
        .message {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }
        .message-in {
            align-items: flex-start;
        }
        .message-out {
            align-items: flex-end;
        }
        .message-content {
            display: flex;
            max-width: 70%;
        }
        .message-in .message-content {
            flex-direction: row;
        }
        .message-out .message-content {
            flex-direction: row-reverse;
        }
        .message-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            margin: 0 10px;
            object-fit: cover;
        }
        .message-text {
            padding: 10px 15px;
            border-radius: 18px;
        }
        .message-in .message-text {
            background-color: #333;
            color: white;
        }
        .message-out .message-text {
            background-color: #990000;
            color: white;
        }
        .message-time {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        .chat-input {
            padding: 15px;
            border-top: 1px solid #333;
            background-color: #222;
        }
        .chat-input textarea {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #444;
            resize: none;
            min-height: 60px;
            margin-bottom: 10px;
            background-color: #333;
            color: #fff;
        }
        .chat-input button {
            padding: 10px 20px;
            background-color: #cc0000;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .no-chats {
            text-align: center;
            padding: 30px;
            color: #ccc;
        }
        .no-chat-selected {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #ccc;
        }
        .profile-link {
            color: #cc0000;
            text-decoration: none;
            font-weight: bold;
        }
        .profile-link:hover {
            color: #ff0000;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <a href="index.php" class="btn-home">Домой</a>
        <h1>Мои сообщения</h1>
        <div></div>
    </header>

    <div class="messages-container">
        <div class="chat-list">
            <?php if ($chats->rowCount() > 0): ?>
                <?php while ($chat = $chats->fetch(PDO::FETCH_ASSOC)): ?>
                    <a href="messages.php?chat_with=<?= $chat['user_id'] ?>" class="chat-item <?= isset($_GET['chat_with']) && $_GET['chat_with'] == $chat['user_id'] ? 'active' : '' ?>">
                        <?php if ($chat['avatar_path']): ?>
                            <img src="<?= htmlspecialchars($chat['avatar_path']) ?>" class="chat-avatar">
                        <?php else: ?>
                            <div class="chat-avatar" style="background:#333;display:flex;align-items:center;justify-content:center;">
                                <span>👤</span>
                            </div>
                        <?php endif; ?>
                        <div class="chat-info">
                            <div class="chat-name"><?= htmlspecialchars($chat['username']) ?></div>
                            <div class="chat-last-message"><?= htmlspecialchars($chat['last_message']) ?></div>
                        </div>
                        <div class="chat-time"><?= date('H:i', strtotime($chat['last_message_time'])) ?></div>
                        <?php if ($chat['unread_count'] > 0): ?>
                            <div class="chat-unread"><?= $chat['unread_count'] ?></div>
                        <?php endif; ?>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-chats">У вас пока нет сообщений</div>
            <?php endif; ?>
        </div>
        
        <div class="chat-window">
            <?php if ($selected_chat): ?>
                <div class="chat-header">
                    <?php if ($selected_chat['avatar_path']): ?>
                        <img src="<?= htmlspecialchars($selected_chat['avatar_path']) ?>" class="chat-avatar-large">
                    <?php else: ?>
                        <div class="chat-avatar-large" style="background:#333;display:flex;align-items:center;justify-content:center;">
                            <span>👤</span>
                        </div>
                    <?php endif; ?>
                    <a href="profile.php?id=<?= $selected_chat['id'] ?>" class="profile-link">
                        <?= htmlspecialchars($selected_chat['username']) ?>
                    </a>
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <?php if (!empty($messages)): ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message message-<?= $message['sender_id'] == $user_id ? 'out' : 'in' ?>">
                                <div class="message-content">
                                    <?php if ($message['sender_id'] != $user_id): ?>
                                        <img src="<?= htmlspecialchars($message['sender_avatar']) ?>" class="message-avatar">
                                    <?php endif; ?>
                                    <div>
                                        <div class="message-text"><?= nl2br(htmlspecialchars($message['message'])) ?></div>
                                        <div class="message-time"><?= $message['time'] ?></div>
                                    </div>
                                    <?php if ($message['sender_id'] == $user_id): ?>
                                        <img src="<?= htmlspecialchars($_SESSION['user']['avatar_path']) ?>" class="message-avatar">
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-messages" style="color:#ccc;">Нет сообщений</div>
                    <?php endif; ?>
                </div>
                
                <div class="chat-input">
                    <form action="send_message.php" method="POST">
                        <input type="hidden" name="recipient_id" value="<?= $selected_chat['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <textarea name="message" placeholder="Введите сообщение..." required></textarea>
                        <button type="submit">Отправить</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="no-chat-selected">Выберите чат для просмотра сообщений</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Автопрокрутка вниз при загрузке чата
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html>
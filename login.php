<?php
require 'config.php';
session_start();

// Логирование запроса
Logger::logRequest();

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

Logger::log("Login attempt: " . print_r($data, true));

// Проверка CSRF-токена
if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
    $error = "Неверный CSRF-токен";
    Logger::logError($error);
    echo json_encode(['success' => false, 'message' => $error]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, username, password, role, is_banned FROM users WHERE username = ?");
    $stmt->execute([$data['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($data['password'], $user['password'])) {
        // Проверка блокировки аккаунта
        if ($user['is_banned']) {
            $error = "Ваш аккаунт заблокирован администратором";
            Logger::logError($error);
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }
        
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];
        
        // Обновляем время сессии
        session_regenerate_id(true);
        
        Logger::log("Login successful: {$user['username']}");
        echo json_encode([
            'success' => true, 
            'user' => $_SESSION['user']
        ]);
    } else {
        $error = "Неверные учетные данные";
        Logger::logError($error);
        echo json_encode(['success' => false, 'message' => $error]);
    }
} catch (PDOException $e) {
    $error = 'Ошибка базы данных: ' . $e->getMessage();
    Logger::logError($error);
    echo json_encode(['success' => false, 'message' => $error]);
}
?>
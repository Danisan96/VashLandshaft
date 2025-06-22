<?php
require 'config.php';
session_start();

// Логирование запроса
Logger::logRequest();

header('Content-Type: application/json');

try {
    // Проверка CSRF-токена
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Неверный CSRF-токен']);
        exit;
    }

    // Получаем имя пользователя до уничтожения сессии
    $username = $_SESSION['user']['username'] ?? 'unknown';
    
    // Уничтожаем сессию
    session_destroy();
    
    // Логирование успешного выхода
    Logger::log("User logged out: $username");
    
    // Отправляем ответ
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Логирование ошибки
    $error = 'Logout error: ' . $e->getMessage();
    Logger::logError($error);
    
    // Отправка ошибки
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $error]);
}
?>
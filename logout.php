<?php
require 'config.php';
session_start();

// Логирование запроса
Logger::logRequest();

try {
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
<?php
require 'config.php';
session_start();

// Логирование запроса
Logger::logRequest();

header('Content-Type: application/json');

try {
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        
        // Логирование проверки авторизации
        Logger::log("User authenticated: {$user['username']} (ID: {$user['id']})");
        
        echo json_encode(['user' => $user]);
    } else {
        // Логирование отсутствия авторизации
        Logger::log("No authenticated user found");
        
        echo json_encode(['user' => null]);
    }
} catch (Exception $e) {
    // Логирование ошибки
    $error = 'Auth check error: ' . $e->getMessage();
    Logger::logError($error);
    
    echo json_encode(['error' => $error]);
}
?>
<?php
require 'config.php';
session_start();

// Логирование запроса
Logger::logRequest();

header('Content-Type: application/json');

// Получение данных
$input = file_get_contents('php://input');
$data = json_decode($input, true);

Logger::log("Register attempt: " . print_r($data, true));

// Проверка CSRF-токена
if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
    $error = "Неверный CSRF-токен";
    Logger::logError($error);
    echo json_encode(['success' => false, 'message' => $error]);
    exit;
}

if (!$data) {
    $error = "Invalid JSON data";
    Logger::logError($error);
    echo json_encode(['success' => false, 'message' => $error]);
    exit;
}

try {
    // Проверка уникальности
    Logger::log("Checking uniqueness for: {$data['username']}, {$data['email']}");
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$data['username'], $data['email']]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        $error = "Имя пользователя или email уже заняты";
        Logger::logError($error);
        echo json_encode(['success' => false, 'message' => $error]);
        exit;
    }

    // Хеширование пароля
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Добавление пользователя
    Logger::log("Inserting user: {$data['username']}");
    
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $result = $stmt->execute([$data['username'], $data['email'], $hashedPassword]);
    
    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        $error = "DB error: " . $errorInfo[2];
        Logger::logError($error);
        echo json_encode(['success' => false, 'message' => $error]);
        exit;
    }
    
    $userId = $pdo->lastInsertId();
    Logger::log("User created successfully: ID $userId");
    
    $_SESSION['user'] = [
        'id' => $userId,
        'username' => $data['username'],
        'role' => 'user'
    ];
    
    // Обновляем время сессии
    session_regenerate_id(true);
    
    echo json_encode([
        'success' => true, 
        'user' => $_SESSION['user']
    ]);
} catch (PDOException $e) {
    $error = 'Ошибка базы данных: ' . $e->getMessage();
    Logger::logError($error);
    echo json_encode(['success' => false, 'message' => $error]);
} catch (Exception $e) {
    $error = 'General error: ' . $e->getMessage();
    Logger::logError($error);
    echo json_encode(['success' => false, 'message' => $error]);
}
?>
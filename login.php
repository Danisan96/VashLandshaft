<?php
require 'config.php';
session_start();

// Логирование запроса
Logger::logRequest();

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

Logger::log("Login attempt: " . print_r($data, true));

try {
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$data['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($data['password'], $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];
        
        Logger::log("Login successful: {$user['username']}");
        echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
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
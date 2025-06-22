<?php
require 'config.php';
session_start();

// Проверка прав администратора
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Проверка CSRF-токена
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Неверный CSRF-токен";
    header("Location: profile.php?id=" . $_POST['user_id']);
    exit;
}

$user_id = (int)$_POST['user_id'];

try {
    // Получаем текущий статус блокировки
    $stmt = $pdo->prepare("SELECT is_banned FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $is_banned = $stmt->fetchColumn();
    
    // Меняем статус на противоположный
    $new_status = $is_banned ? 0 : 1;
    
    // Обновляем статус
    $stmt = $pdo->prepare("UPDATE users SET is_banned = ? WHERE id = ?");
    $stmt->execute([$new_status, $user_id]);
    
    // Логируем действие
    $action = $new_status ? 'заблокирован' : 'разблокирован';
    Logger::log("Пользователь ID $user_id $action администратором ID " . $_SESSION['user']['id']);
    
    $_SESSION['success'] = "Статус пользователя успешно изменен";
} catch (PDOException $e) {
    Logger::logError("Ошибка блокировки пользователя: " . $e->getMessage());
    $_SESSION['error'] = "Ошибка при изменении статуса пользователя";
}

header("Location: profile.php?id=$user_id");
exit;
?>
<?php
require 'config.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: index.php");
    exit;
}

// Проверка CSRF-токена
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Неверный CSRF-токен";
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

$sender_id = $_SESSION['user']['id'];
$recipient_id = (int)$_POST['recipient_id'];
$service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : null;
$message = trim($_POST['message']);

// Валидация
if (empty($message)) {
    $_SESSION['error'] = "Сообщение не может быть пустым";
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

try {
    // Проверяем, существует ли получатель
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$recipient_id]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = "Получатель не найден";
        header("Location: index.php");
        exit;
    }

    // Сохраняем сообщение
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, recipient_id, service_id, message)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$sender_id, $recipient_id, $service_id, $message]);

    $_SESSION['success'] = "Сообщение успешно отправлено";
    Logger::log("Message sent from $sender_id to $recipient_id");

} catch (PDOException $e) {
    Logger::logError("Message send error: " . $e->getMessage());
    $_SESSION['error'] = "Ошибка при отправке сообщения";
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit;
?>
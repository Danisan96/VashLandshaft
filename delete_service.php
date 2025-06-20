<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['service_id'])) {
    $service_id = (int)$_POST['service_id'];
    
    // Получаем информацию об услуге
    $stmt = $pdo->prepare("
        SELECT s.*, u.id AS user_id 
        FROM services s
        JOIN users u ON s.created_by = u.id
        WHERE s.id = ?
    ");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service) {
        $_SESSION['error'] = 'Услуга не найдена.';
        header("Location: index.php");
        exit;
    }
    
    // Проверяем права: только владелец или админ
    $is_owner = ($_SESSION['user']['id'] == $service['user_id']);
    $is_admin = ($_SESSION['user']['role'] == 'admin');
    
    if (!$is_owner && !$is_admin) {
        $_SESSION['error'] = 'У вас нет прав для удаления этой услуги.';
        header("Location: index.php");
        exit;
    }
    
    try {
        // "Мягкое" удаление (изменение флага)
        $stmt = $pdo->prepare("
            UPDATE services 
            SET is_deleted = 1 
            WHERE id = ?
        ");
        $stmt->execute([$service_id]);
        
        $_SESSION['success'] = 'Услуга успешно удалена.';
    } catch (PDOException $e) {
        Logger::logError("Service delete failed: " . $e->getMessage());
        $_SESSION['error'] = 'Ошибка при удалении услуги.';
    }
}

// Возвращаем на предыдущую страницу
header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit;
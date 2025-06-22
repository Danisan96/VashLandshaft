<?php
require 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['service_id'])) {
    $service_id = (int)$_POST['service_id'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE services 
            SET is_deleted = 0 
            WHERE id = ?
        ");
        $stmt->execute([$service_id]);
        
        $_SESSION['success'] = 'Услуга успешно восстановлена.';
    } catch (PDOException $e) {
        Logger::logError("Service restore failed: " . $e->getMessage());
        $_SESSION['error'] = 'Ошибка при восстановлении услуги.';
    }
}

header("Location: admin.php");
exit;
?>
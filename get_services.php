<?php
require 'config.php';

// Логирование запроса
Logger::logRequest();

header('Content-Type: application/json');

try {
    // Логирование начала операции
    Logger::log("Fetching services list");
    
    $services = [];
    $stmt = $pdo->query("
        SELECT s.id, s.title, s.description, u.username AS author
        FROM services s
        JOIN users u ON s.created_by = u.id
        ORDER BY s.created_at DESC
    ");
    
    // Логирование количества найденных услуг
    $serviceCount = 0;
    
    while ($service = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $serviceId = $service['id'];
        
        // Получение изображений для услуги
        $imgStmt = $pdo->prepare("SELECT id, file_path FROM service_images WHERE service_id = ?");
        $imgStmt->execute([$serviceId]);
        $service['images'] = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $services[] = $service;
        $serviceCount++;
    }
    
    // Логирование результата
    Logger::log("Fetched $serviceCount services");
    
    echo json_encode($services);
} catch (PDOException $e) {
    // Логирование ошибки БД
    $error = 'Database error: ' . $e->getMessage();
    Logger::logError($error);
    
    echo json_encode(['error' => $error]);
} catch (Exception $e) {
    // Логирование общей ошибки
    $error = 'Unexpected error: ' . $e->getMessage();
    Logger::logError($error);
    
    echo json_encode(['error' => $error]);
}
?>
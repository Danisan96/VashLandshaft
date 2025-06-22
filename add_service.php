<?php
require 'config.php';
session_start();

// Логирование запроса
Logger::logRequest();

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    $error = "Не авторизован";
    Logger::logError($error);
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => $error]);
    exit;
}

Logger::log("Add service attempt by user: {$_SESSION['user']['username']}");

try {
    $pdo->beginTransaction();
    
    // Добавление услуги
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = (float)$_POST['price']; // Исправлено: получаем цену
    
    Logger::log("Adding service: $title (Price: $price)");
    
    $stmt = $pdo->prepare("INSERT INTO services (title, description, price, created_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $description, $price, $_SESSION['user']['id']]);
    $serviceId = $pdo->lastInsertId();
    
    // Обработка изображений
    $uploadedFiles = [];
    if (!empty($_FILES['photos'])) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            Logger::log("Created upload directory: $uploadDir");
        }
        
        Logger::log("Processing " . count($_FILES['photos']['tmp_name']) . " files");
        
        foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
            $originalName = $_FILES['photos']['name'][$key];
            $fileName = uniqid() . '_' . basename($originalName);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($tmpName, $targetPath)) {
                $stmt = $pdo->prepare("INSERT INTO service_images (service_id, file_path) VALUES (?, ?)");
                $stmt->execute([$serviceId, $targetPath]);
                $uploadedFiles[] = $targetPath;
                Logger::log("File uploaded: $originalName -> $targetPath");
            } else {
                $error = "Failed to move uploaded file: $originalName";
                Logger::logError($error);
            }
        }
    }
    
    $pdo->commit();
    Logger::log("Service added successfully: ID $serviceId");
    echo json_encode(['success' => true, 'message' => 'Услуга добавлена', 'service_id' => $serviceId]);
} catch (Exception $e) {
    $pdo->rollBack();
    $error = 'Ошибка: ' . $e->getMessage();
    Logger::logError($error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $error]);
}
?>
<?php
require 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Получаем все услуги (включая удаленные)
$stmt = $pdo->query("
    SELECT s.*, u.username 
    FROM services s
    JOIN users u ON s.created_by = u.id
    ORDER BY s.created_at DESC
");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <!-- ... стили ... -->
    <title>Админ-панель</title>
    <style>
        .deleted-service {
            opacity: 0.6;
            background-color: #ffe6e6;
        }
        .restore-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <!-- Шапка сайта -->
    
    <div class="container">
        <h1>Админ-панель управления услугами</h1>
        
        <div class="services">
            <?php foreach ($services as $service): ?>
                <div class="service <?= $service['is_deleted'] ? 'deleted-service' : '' ?>">
                    <h3><?= htmlspecialchars($service['title']) ?></h3>
                    <p><?= nl2br(htmlspecialchars($service['description'])) ?></p>
                    <small>
                        Автор: <?= htmlspecialchars($service['username']) ?><br>
                        Добавлено: <?= date('d.m.Y H:i', strtotime($service['created_at'])) ?>
                    </small>
                    
                    <?php if ($service['is_deleted']): ?>
                        <form action="restore_service.php" method="POST">
                            <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                            <button type="submit" class="restore-btn">Восстановить</button>
                        </form>
                    <?php else: ?>
                        <form action="delete_service.php" method="POST" 
                              onsubmit="return confirm('Вы уверены, что хотите удалить эту услугу?');">
                            <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                            <button type="submit" class="delete-btn">Удалить</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
<?php
require 'config.php';

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$user_id) {
    header("Location: index.php");
    exit;
}

// Получаем данные пользователя
$stmt = $pdo->prepare("
    SELECT id, username, email, created_at, 
           profile_description, avatar_path 
    FROM users 
    WHERE id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: index.php");
    exit;
}

// Получаем услуги пользователя
$stmt = $pdo->prepare("
    SELECT * FROM services 
    WHERE created_by = ? AND is_deleted = 0
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <!-- ... стили ... -->
    <title>Профиль: <?= htmlspecialchars($user['username']) ?></title>
    <style>
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 30px;
            border: 3px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .profile-info {
            flex-grow: 1;
        }
        .profile-description {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .edit-profile-btn {
            background-color: #4CAF50;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Шапка сайта -->

    <div class="container">
        <div class="profile-header">
            <?php if ($user['avatar_path']): ?>
                <img src="<?= htmlspecialchars($user['avatar_path']) ?>" 
                     alt="Аватар" class="profile-avatar">
            <?php else: ?>
                <div class="avatar-placeholder" style="width:120px;height:120px;"></div>
            <?php endif; ?>
            
            <div class="profile-info">
                <h1><?= htmlspecialchars($user['username']) ?></h1>
                <p>Зарегистрирован: <?= date('d.m.Y', strtotime($user['created_at'])) ?></p>
                
                <?php if (isset($_SESSION['user']) && $_SESSION['user']['id'] == $user_id): ?>
                    <a href="edit_profile.php" class="edit-profile-btn">Редактировать профиль</a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($user['profile_description'])): ?>
            <div class="profile-description">
                <h3>О себе:</h3>
                <p><?= nl2br(htmlspecialchars($user['profile_description'])) ?></p>
            </div>
        <?php endif; ?>
        
        <h2>Услуги пользователя</h2>
        <div class="services">
            <?php if (count($services) > 0): ?>
                <?php foreach ($services as $service): ?>
                    <div class="service">
                        <h3><?= htmlspecialchars($service['title']) ?></h3>
                        <p><?= nl2br(htmlspecialchars($service['description'])) ?></p>
                        <!-- Кнопка удаления как в index.php -->
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Пользователь еще не добавил услуги.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$error = '';
$success = '';

// Получаем текущие данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $description = $_POST['description'] ?? '';
    
    // Обработка загрузки аватарки
    $avatar_path = $user['avatar_path'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
        $target_path = AVATAR_UPLOAD_DIR . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            // Удаляем старый аватар, если он есть
            if ($avatar_path && file_exists($avatar_path)) {
                unlink($avatar_path);
            }
            $avatar_path = 'uploads/avatars/' . $filename;
        } else {
            $error = 'Ошибка загрузки аватарки.';
        }
    }
    
    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET profile_description = ?, avatar_path = ?
                WHERE id = ?
            ");
            $stmt->execute([$description, $avatar_path, $user_id]);
            
            // Обновляем данные в сессии
            $_SESSION['user']['avatar_path'] = $avatar_path;
            $success = 'Профиль успешно обновлен!';
            
            // Обновляем локальные данные
            $user['profile_description'] = $description;
            $user['avatar_path'] = $avatar_path;
        } catch (PDOException $e) {
            $error = 'Ошибка базы данных: ' . $e->getMessage();
            Logger::logError("Profile update failed: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <!-- ... стили ... -->
    <title>Редактирование профиля</title>
    <style>
        .avatar-preview {
            max-width: 200px;
            max-height: 200px;
            margin-bottom: 15px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <!-- Шапка сайта -->

    <div class="container">
        <h1>Редактирование профиля</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Описание профиля:</label>
                <textarea name="description" rows="5"><?= 
                    htmlspecialchars($user['profile_description'] ?? '') 
                ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Аватарка:</label>
                <?php if ($user['avatar_path']): ?>
                    <img src="<?= htmlspecialchars($user['avatar_path']) ?>" 
                         class="avatar-preview" id="avatarPreview">
                <?php else: ?>
                    <div class="avatar-placeholder" id="avatarPreview"></div>
                <?php endif; ?>
                
                <input type="file" name="avatar" id="avatarInput" accept="image/*">
            </div>
            
            <button type="submit" class="submit-btn">Сохранить изменения</button>
        </form>
    </div>

    <script>
        // Превью аватарки перед загрузкой
        document.getElementById('avatarInput').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                const preview = document.getElementById('avatarPreview');
                
                reader.onload = function(e) {
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        // Создаем изображение, если был плейсхолдер
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'avatar-preview';
                        preview.replaceWith(img);
                        preview = img;
                    }
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
</body>
</html>
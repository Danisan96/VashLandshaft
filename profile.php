<?php
require 'config.php';

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Получаем ID профиля из запроса (по умолчанию - текущий пользователь)
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user']['id'];

// Проверяем права доступа
$is_own_profile = ($profile_id == $_SESSION['user']['id']);
$is_admin = ($_SESSION['user']['role'] === 'admin');

if (!$is_own_profile && !$is_admin) {
    header("Location: index.php");
    exit;
}

$user_id = $profile_id;
$error = '';
$success = '';

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: index.php");
    exit;
}

// Получаем услуги пользователя
$services = [];
try {
    $stmt = $pdo->prepare("
        SELECT * 
        FROM services 
        WHERE created_by = ? AND is_deleted = 0
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Ошибка загрузки услуг: ' . $e->getMessage();
    Logger::logError($error);
}

// Обработка формы редактирования (только для своего профиля)
if ($is_own_profile && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $description = $_POST['description'] ?? '';
    
    // Обработка загрузки аватарки
    $avatar_path = $user['avatar_path'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
        $target_path = AVATAR_UPLOAD_DIR . $filename;
        
        // Проверка типа файла
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($file['tmp_name']);
        
        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Удаляем старый аватар
                if ($avatar_path && file_exists($avatar_path)) {
                    unlink($avatar_path);
                }
                $avatar_path = 'uploads/avatars/' . $filename;
            } else {
                $error = 'Ошибка загрузки аватарки.';
            }
        } else {
            $error = 'Недопустимый тип файла. Разрешены только JPG, PNG и GIF.';
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль пользователя</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #111;
            color: #fff;
        }
        header {
            background-color: #000;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #cc0000;
        }
        .profile-container {
            max-width: 800px;
            margin: 30px auto;
            background: #222;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
            border: 1px solid #333;
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #333;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 30px;
            border: 3px solid #333;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .profile-info {
            flex-grow: 1;
        }
        .profile-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background-color: #cc0000;
            color: white;
        }
        .btn-primary:hover {
            background-color: #990000;
        }
        .btn-danger {
            background-color: #333;
            color: white;
            border: 1px solid #cc0000;
        }
        .btn-danger:hover {
            background-color: #222;
        }
        .btn-success {
            background-color: #990000;
            color: white;
        }
        .profile-description {
            background-color: #333;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            color: #eee;
        }
        .error {
            color: #ff6666;
            background-color: #330000;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .success {
            color: #66ff66;
            background-color: #003300;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .edit-form {
            display: none;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #ccc;
        }
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #444;
            border-radius: 4px;
            font-size: 16px;
            min-height: 150px;
            background-color: #333;
            color: #fff;
        }
        input[type="file"] {
            margin-top: 5px;
        }
        .user-services {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #333;
        }
        .service-item {
            background: #333;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            padding: 15px;
            margin-bottom: 15px;
        }
        .service-item h3 {
            margin-top: 0;
            color: #fff;
        }
        .admin-actions {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #333;
        }
        .admin-badge {
            background-color: #990000;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 8px;
        }
        .status-badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 8px;
        }
        .banned {
            background-color: #cc0000;
            color: white;
        }
        .active {
            background-color: #009900;
            color: white;
        }
        .nav-buttons {
            display: flex;
            gap: 10px;
        }
        #avatarPreview {
            border: 1px solid #444;
        }
    </style>
</head>
<body>
    <header>
        <div class="nav-buttons">
            <a href="index.php" class="btn btn-primary">На главную</a>
            <?php if ($is_admin): ?>
                <a href="admin.php" class="btn btn-success">Админ-панель</a>
            <?php endif; ?>
        </div>
        <div class="auth-buttons">
            <?php if ($is_own_profile): ?>
                <button id="logoutBtn" class="btn btn-danger">Выход</button>
            <?php endif; ?>
        </div>
    </header>

    <div class="profile-container">
        <div class="profile-header">
            <?php if ($user['avatar_path']): ?>
                <img src="<?= htmlspecialchars($user['avatar_path']) ?>" 
                     alt="Аватар" class="profile-avatar">
            <?php else: ?>
                <div class="profile-avatar" style="background:#333;display:flex;align-items:center;justify-content:center;">
                    <span style="font-size:40px;color:#999;">👤</span>
                </div>
            <?php endif; ?>
            
            <div class="profile-info">
                <h1>
                    <?= htmlspecialchars($user['username']) ?>
                    <?php if ($user['role'] === 'admin'): ?>
                        <span class="admin-badge">ADMIN</span>
                    <?php endif; ?>
                    <span class="status-badge <?= $user['is_banned'] ? 'banned' : 'active' ?>">
                        <?= $user['is_banned'] ? 'ЗАБЛОКИРОВАН' : 'АКТИВЕН' ?>
                    </span>
                </h1>
                <p>Зарегистрирован: <?= date('d.m.Y', strtotime($user['created_at'])) ?></p>
                
                <?php if ($is_own_profile): ?>
                    <div class="profile-actions">
                        <button id="editProfileBtn" class="btn btn-primary">Редактировать профиль</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        
        <div class="profile-description">
            <h3>О себе:</h3>
            <p><?= $user['profile_description'] 
                ? nl2br(htmlspecialchars($user['profile_description'])) 
                : 'Пользователь пока не добавил информацию о себе' ?></p>
        </div>
        
        <!-- Услуги пользователя -->
        <div class="user-services">
            <h2>Услуги пользователя</h2>
            <?php if (count($services) > 0): ?>
                <?php foreach ($services as $service): ?>
                    <div class="service-item">
                        <h3><?= htmlspecialchars($service['title']) ?></h3>
                        <p><?= nl2br(htmlspecialchars($service['description'])) ?></p>
                        <small>Создано: <?= date('d.m.Y H:i', strtotime($service['created_at'])) ?></small>
                        
                        <?php if ($is_own_profile || $is_admin): ?>
                            <form action="delete_service.php" method="POST" onsubmit="return confirm('Вы уверены?')" style="margin-top:10px;">
                                <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <button type="submit" class="btn btn-danger">Удалить услугу</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Пользователь еще не добавил услуги.</p>
            <?php endif; ?>
        </div>
        
        <!-- Админские действия (блокировка/разблокировка) -->
        <?php if ($is_admin && !$is_own_profile): ?>
            <div class="admin-actions">
                <h2>Административные действия</h2>
                <form method="POST" action="toggle_user_ban.php">
                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <?php if ($user['is_banned']): ?>
                        <button type="submit" class="btn btn-success">Разблокировать пользователя</button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-danger">Заблокировать пользователя</button>
                    <?php endif; ?>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Форма редактирования профиля -->
        <?php if ($is_own_profile): ?>
            <div id="editProfileForm" class="edit-form">
                <h2>Редактирование профиля</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="form-group">
                        <label>Описание профиля:</label>
                        <textarea name="description"><?= 
                            htmlspecialchars($user['profile_description'] ?? '') 
                        ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Аватарка:</label>
                        <?php if ($user['avatar_path']): ?>
                            <img src="<?= htmlspecialchars($user['avatar_path']) ?>" 
                                 class="profile-avatar" id="avatarPreview" style="width:80px;height:80px;margin-bottom:15px;">
                        <?php else: ?>
                            <div class="profile-avatar" id="avatarPreview" style="width:80px;height:80px;margin-bottom:15px;background:#333;display:flex;align-items:center;justify-content:center;">
                                <span style="font-size:30px;color:#999;">👤</span>
                            </div>
                        <?php endif; ?>
                        
                        <input type="file" name="avatar" id="avatarInput" accept="image/*">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    <button type="button" id="cancelEdit" class="btn btn-danger">Отмена</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Элементы управления
        const editProfileBtn = document.getElementById('editProfileBtn');
        const editProfileForm = document.getElementById('editProfileForm');
        const cancelEdit = document.getElementById('cancelEdit');
        const avatarInput = document.getElementById('avatarInput');
        const avatarPreview = document.getElementById('avatarPreview');
        const logoutBtn = document.getElementById('logoutBtn');
        
        // Показать форму редактирования
        if (editProfileBtn) {
            editProfileBtn.onclick = () => {
                editProfileForm.style.display = 'block';
                window.scrollTo(0, document.body.scrollHeight);
            };
        }
        
        // Скрыть форму редактирования
        if (cancelEdit) {
            cancelEdit.onclick = () => {
                editProfileForm.style.display = 'none';
            };
        }
        
        // Превью аватарки
        if (avatarInput) {
            avatarInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        if (avatarPreview.tagName === 'IMG') {
                            avatarPreview.src = e.target.result;
                        } else {
                            // Создаем изображение, если был плейсхолдер
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'profile-avatar';
                            img.style.width = '80px';
                            img.style.height = '80px';
                            img.style.marginBottom = '15px';
                            avatarPreview.replaceWith(img);
                            avatarPreview = img;
                        }
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
        
        // Выход из профиля
        if (logoutBtn) {
            logoutBtn.onclick = async () => {
                try {
                    const response = await fetch('logout.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ 
                            csrf_token: "<?= $_SESSION['csrf_token'] ?>" 
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        window.location.href = 'index.php';
                    }
                } catch (error) {
                    alert('Ошибка выхода');
                }
            };
        }
    </script>
</body>
</html>
<?php
require 'config.php';

// Проверка прав администратора
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Получаем всех пользователей
$users = [];
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    Logger::logError("Ошибка загрузки пользователей: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
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
        .admin-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            background: #222;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
            border: 1px solid #333;
        }
        .user-list {
            margin-top: 20px;
        }
        .user-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #333;
        }
        .user-item.banned {
            background-color: #330000;
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        .user-info {
            flex-grow: 1;
        }
        .user-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-view {
            background-color: #333;
            color: white;
            border: 1px solid #cc0000;
        }
        .btn-ban {
            background-color: #cc0000;
            color: white;
        }
        .btn-unban {
            background-color: #009900;
            color: white;
        }
        .btn-logout {
            background-color: #333;
            color: white;
            border: 1px solid #cc0000;
        }
        .btn-home {
            background-color: #cc0000;
            color: white;
        }
    </style>
</head>
<body>
    <header>
        <div class="auth-buttons">
            <a href="index.php" class="btn btn-home">На главную</a>
            <button id="logoutBtn" class="btn btn-logout">Выход</button>
        </div>
    </header>

    <div class="admin-container">
        <h1>Административная панель</h1>
        <h2>Управление пользователями</h2>
        
        <div class="user-list">
            <?php foreach ($users as $user): ?>
                <div class="user-item <?= $user['is_banned'] ? 'banned' : '' ?>">
                    <?php if ($user['avatar_path']): ?>
                        <img src="<?= htmlspecialchars($user['avatar_path']) ?>" alt="Аватар" class="user-avatar">
                    <?php else: ?>
                        <div class="user-avatar" style="background:#333;display:flex;align-items:center;justify-content:center;">
                            <span>👤</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="user-info">
                        <h3><?= htmlspecialchars($user['username']) ?>
                            <?php if ($user['is_banned']): ?>
                                <span style="color:#ff6666;font-size:14px;">(заблокирован)</span>
                            <?php endif; ?>
                        </h3>
                        <p>Роль: <?= $user['role'] === 'admin' ? 'Администратор' : 'Пользователь' ?></p>
                        <p>Зарегистрирован: <?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></p>
                    </div>
                    
                    <div class="user-actions">
                        <a href="profile.php?id=<?= $user['id'] ?>" class="btn btn-view">Профиль</a>
                        
                        <?php if ($user['id'] != $_SESSION['user']['id']): ?>
                            <?php if ($user['is_banned']): ?>
                                <form method="POST" action="toggle_user_ban.php">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="btn btn-unban">Разблокировать</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="toggle_user_ban.php">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="btn btn-ban">Заблокировать</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Выход из системы
        document.getElementById('logoutBtn').onclick = async () => {
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
    </script>
</body>
</html>